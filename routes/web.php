<?php

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentTransmittal;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\VerifyUserEmailController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Services\DocumentDownloadService;
use App\Services\DocumentQrToken;
use App\Services\DocumentStatusTimeline;
use App\Services\AuditLogService;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;
use App\Http\Middleware\EnsureLegalStaff;


Route::view('/ai-test', 'ai-test');

Route::post('/chatbot/message', [
    ChatbotController::class,
    'reply',
])->middleware(['auth', 'throttle:chatbot'])->name('chatbot.message');

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/login', 'home')->name('login');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->middleware('throttle:google-login')
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/email/verify/{id}/{hash}', VerifyUserEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/api/user', function (Request $request) {
    return response()->json([
        'authenticated' => true,
        'user' => $request->user(),
    ]);
})->middleware('auth');

Route::post('/logout', function (Request $request) {
    app(AuditLogService::class)->record(
        'User logged out',
        'Administrator logged out.',
        $request->user(),
    );

    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
        'message' => 'Logged out successfully',
    ]);
})->middleware('auth');

Route::get('/admin/users/export', [UserExportController::class, '__invoke'])
    ->middleware(['auth', AdminMiddleware::class, EnsureSuperAdmin::class])
    ->name('admin.users.export');

Route::get('/admin/document-export', [DocumentExportController::class, '__invoke'])
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.export');

Route::get('/admin/reports/monthly', App\Http\Controllers\MonthlyReportController::class)
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.reports.monthly');

Route::get('/document-status/{document}', function (int $document) {
    $documentRecord = Document::query()
        ->with(['user'])
        ->findOrFail($document);

    return response()
        ->view('documents.public-status', [
            'document' => $documentRecord,
        ])
        ->header('Cache-Control', 'no-store, private')
        ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
})
    ->middleware('signed')
    ->name('documents.public-status');


/*
|--------------------------------------------------------------------------
| Client document preview
|--------------------------------------------------------------------------
*/

Route::get('/client/document-preview/{document}', function (string $document) {

    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $storage = app(\App\Services\DocumentStorageService::class);

    // Prefer the transmittal attachment.
    $transmittal = $documentRecord->transmittalAttachments()->first();

    $filePath = $transmittal?->file_path
        ?: $documentRecord->transmittal
        ?: $versionRecord?->file_path;

    $preferredDisk = $transmittal?->file_path
        ? ($transmittal->file_disk ?: 'local')
        : ($filePath === $versionRecord?->file_path
            ? ($versionRecord?->file_disk ?: 'local')
            : 'local');

    // Fall back to the document version if the transmittal is unavailable.
    if (
        (! $filePath || ! $storage->exists($filePath, $preferredDisk))
        && $versionRecord?->file_path
    ) {
        $filePath = $versionRecord->file_path;
        $preferredDisk = $versionRecord->file_disk ?: 'local';
    }

    abort_unless(
        $filePath && $storage->exists($filePath, $preferredDisk),
        404,
    );

    $displayName = $filePath === $versionRecord?->file_path
        ? ($versionRecord?->original_filename ?: basename($filePath))
        : ($transmittal?->original_filename ?: basename($filePath));

    return $storage->withLocalPath(
        $filePath,
        fn (string $localPath) => app(DocumentDownloadService::class)->preview(
            $documentRecord,
            $localPath,
            $displayName,
        ),
        $preferredDisk,
    );

})
    ->middleware('auth')
    ->name('client.document.preview');

Route::get('/client/document-thumbnail/{document}', function (string $document) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $storage = app(\App\Services\DocumentStorageService::class);

    $transmittal = $documentRecord->transmittalAttachments()->first();

    $filePath = $transmittal?->file_path
        ?: $documentRecord->transmittal
        ?: $versionRecord?->file_path;

    $preferredDisk = $transmittal?->file_path
        ? ($transmittal->file_disk ?: 'local')
        : ($filePath === $versionRecord?->file_path
            ? ($versionRecord?->file_disk ?: 'local')
            : 'local');

    if (
        (! $filePath || ! $storage->exists($filePath, $preferredDisk))
        && $versionRecord?->file_path
    ) {
        $filePath = $versionRecord->file_path;
        $preferredDisk = $versionRecord->file_disk ?: 'local';
    }

    abort_unless(
        $filePath && $storage->exists($filePath, $preferredDisk),
        404,
    );

    return $storage->withLocalPath(
        $filePath,
        fn (string $localPath) => app(
            \App\Services\DocumentPreviewService::class
        )->thumbnail($localPath),
        $preferredDisk,
    );
})
    ->middleware('auth')
    ->name('client.document.thumbnail');

Route::get('/client/document-download/{document}', function (string $document) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    abort_unless(
        $versionRecord?->file_path
        && $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404,
    );

    return app(DocumentDownloadService::class)->download($documentRecord, $versionRecord);
})
    ->middleware(['auth', 'throttle:downloads'])
    ->name('client.document.download');

Route::get('/client/document-version/{document}/{version}/preview', function (
    string $document,
    int $version,
) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404,
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($version);

    $latestAdminRevisionId = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->where('source', 'admin')
        ->latest('created_at')
        ->latest('version_id')
        ->value('version_id');

    abort_unless(
        $versionRecord->source === 'client'
        || (int) $versionRecord->version_id === (int) $latestAdminRevisionId,
        404,
    );

    abort_unless(
        $versionRecord->file_path
        && $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $versionRecord->file_path,
        fn (string $localPath) => app(DocumentDownloadService::class)->preview(
            $documentRecord,
            $localPath,
            $versionRecord->original_filename ?: basename($versionRecord->file_path),
        ),
        $versionRecord->file_disk ?: 'local',
    );
})
    ->middleware('auth')
    ->name('client.document.version.preview');

Route::get('/client/document-version/{document}/{version}/download', function (
    string $document,
    int $version,
) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404,
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($version);

    $latestAdminRevisionId = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->where('source', 'admin')
        ->latest('created_at')
        ->latest('version_id')
        ->value('version_id');

    abort_unless(
        $versionRecord->source === 'client'
        || (int) $versionRecord->version_id === (int) $latestAdminRevisionId,
        404,
    );

    abort_unless(
        $versionRecord->file_path
        && $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404,
    );

    return app(DocumentDownloadService::class)->download($documentRecord, $versionRecord);
})
    ->middleware('auth')
    ->name('client.document.version.download');

Route::get('/client/document-transmittal/{document}/preview', function (string $document) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404,
    );

    $filePath = $documentRecord->transmittal;

    $attachment = $filePath
        ? $documentRecord->transmittalAttachments()
            ->where('file_path', $filePath)
            ->first()
        : null;

    $diskName = $attachment?->file_disk ?: 'local';

    abort_unless(
        $filePath
        && Storage::disk($diskName)->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $filePath,
        fn (string $localPath) => app(DocumentDownloadService::class)->preview(
            $documentRecord,
            $localPath,
            $attachment?->original_filename ?: basename($filePath),
        ),
        $diskName,
    );
})
    ->middleware('auth')
    ->name('client.document.transmittal.preview');

Route::get('/client/document-transmittal/{document}/download', function (string $document) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404,
    );

    $filePath = $documentRecord->transmittal;
    $attachment = $filePath
        ? $documentRecord->transmittalAttachments()
            ->where('file_path', $filePath)
            ->first()
        : null;
    $diskName = $attachment?->file_disk ?: 'local';

    abort_unless(
        $filePath && Storage::disk($diskName)->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->downloadResponse(
        $filePath,
        $attachment?->original_filename ?: basename($filePath),
        null,
        $diskName,
    );
})
    ->middleware('auth')
    ->name('client.document.transmittal.download');

Route::get('/client/document-transmittal/{document}/{attachment}/preview', function (
    string $document,
    int $attachment,
) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404,
    );

    $attachmentRecord = DocumentTransmittal::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($attachment);
    $filePath = $attachmentRecord->file_path;
    $diskName = $attachmentRecord->file_disk ?: 'local';

    abort_unless(
        $filePath
        && $attachmentRecord->storageDisk()->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $filePath,
        fn (string $localPath) => app(DocumentDownloadService::class)->preview(
            $documentRecord,
            $localPath,
            $attachmentRecord->original_filename ?: basename($filePath),
        ),
        $diskName,
    );
})
    ->middleware('auth')
    ->name('client.document.transmittal-attachment.preview');

Route::get('/client/document-transmittal/{document}/{attachment}/download', function (
    string $document,
    int $attachment,
) {
    $documentRecord = Document::findForRoute($document);

    abort_unless(
        (int) $documentRecord->user_id === (int) auth()->id()
        || $documentRecord
            ->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->exists(),
        404,
    );

    $attachmentRecord = DocumentTransmittal::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($attachment);
    $filePath = $attachmentRecord->file_path;
    $diskName = $attachmentRecord->file_disk ?: 'local';

    abort_unless(
        $filePath && $attachmentRecord->storageDisk()->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->downloadResponse(
        $filePath,
        $attachmentRecord->original_filename ?: basename($filePath),
        null,
        $diskName,
    );
})
    ->middleware('auth')
    ->name('client.document.transmittal-attachment.download');

Route::get('/messages/{message}/attachment/{attachment}', function (
    Message $message,
    MessageAttachment $attachment
) {
    abort_unless(
        (int) $attachment->message_id === (int) $message->id,
        404
    );

    $conversation = $message->conversation;

    abort_unless($conversation, 404);

    Gate::authorize('view', $conversation);

    $filePath = $attachment->path;
    $diskName = $attachment->disk ?: 'local';
    $storage = app(\App\Services\DocumentStorageService::class);

    abort_unless($filePath && $storage->exists($filePath, $diskName), 404);

    $fileName = basename($attachment->original_name ?: $filePath);
    $mimeType = $attachment->mime_type;

    if (! $mimeType) {
        try {
            $mimeType = $storage->disk($diskName)->mimeType($filePath);
        } catch (\Throwable) {
            $mimeType = null;
        }
    }

    $mimeType ??= 'application/octet-stream';
    $quotedFileName = addcslashes($fileName, "\\\"");

    if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf') {
        return $storage->withLocalPath(
            $filePath,
            fn (string $localPath) => response()->file($localPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $quotedFileName . '"',
            ]),
            $diskName,
        );
    }

    return $storage->downloadResponse(
        $filePath,
        $fileName,
        $mimeType,
        $diskName,
    );
})
    ->middleware('auth')
    ->name('messages.attachment');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');


Route::get('/admin/document-temp-preview/{file}', function (string $file) {
    $path = storage_path('app/private/temp-previews/'.$file);

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$file.'"',
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'private, no-store',
    ]);
})
    ->where('file', '[a-f0-9]{32}\.pdf')
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.document.temp-preview');

Route::get('/admin/documents/{document}/preview', function (string $document) {

    $documentRecord = Document::findForRoute($document);
    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $filePath = $versionRecord?->file_path;

    abort_unless(
        $filePath &&
        $versionRecord->storageDisk()->exists($filePath),
        404
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $filePath,
        fn (string $localPath) => app(
            \App\Services\DocumentPreviewService::class
        )->preview($localPath),
        $versionRecord->file_disk ?: 'local',
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.preview');

Route::get('/admin/documents/{document}/thumbnail', function (string $document) {
    $documentRecord = Document::findForRoute($document);
    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $filePath = $versionRecord?->file_path;

    abort_unless(
        $filePath
        && $versionRecord->storageDisk()->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $filePath,
        fn (string $localPath) => app(
            \App\Services\DocumentPreviewService::class
        )->thumbnail($localPath),
        $versionRecord->file_disk ?: 'local',
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.thumbnail');

Route::get('/admin/documents/{document}/transmittal-preview', function (string $document) {
    $documentRecord = Document::findForRoute($document);
    $filePath = $documentRecord->transmittal;

    $attachment = $filePath
        ? $documentRecord->transmittalAttachments()
            ->where('file_path', $filePath)
            ->first()
        : null;

    $diskName = $attachment?->file_disk ?: 'local';

    abort_unless(
        $filePath
        && Storage::disk($diskName)->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $filePath,
        fn (string $localPath) => app(
            \App\Services\DocumentPreviewService::class
        )->preview($localPath),
        $diskName,
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.transmittal.preview');

Route::get('/admin/documents/{document}/transmittal-download', function (string $document) {
    $documentRecord = Document::findForRoute($document);
    $filePath = $documentRecord->transmittal;
    $attachment = $filePath
        ? $documentRecord->transmittalAttachments()
            ->where('file_path', $filePath)
            ->first()
        : null;
    $diskName = $attachment?->file_disk ?: 'local';

    abort_unless(
        $filePath && Storage::disk($diskName)->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->downloadResponse(
        $filePath,
        $attachment?->original_filename ?: basename($filePath),
        null,
        $diskName,
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.transmittal.download');

Route::get('/admin/documents/{document}/transmittals/{attachment}/preview', function (
    string $document,
    int $attachment,
) {
    $documentRecord = Document::findForRoute($document);
    $attachmentRecord = DocumentTransmittal::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($attachment);
    $filePath = $attachmentRecord->file_path;
    $diskName = $attachmentRecord->file_disk ?: 'local';

    abort_unless(
        $filePath &&
        $attachmentRecord->storageDisk()->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $filePath,
        fn (string $localPath) => app(
            \App\Services\DocumentPreviewService::class
        )->preview($localPath),
        $diskName,
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.transmittal-attachment.preview');

Route::get('/admin/documents/{document}/transmittals/{attachment}/download', function (
    string $document,
    int $attachment,
) {
    $documentRecord = Document::findForRoute($document);
    $attachmentRecord = DocumentTransmittal::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($attachment);
    $filePath = $attachmentRecord->file_path;
    $diskName = $attachmentRecord->file_disk ?: 'local';

    abort_unless(
        $filePath && $attachmentRecord->storageDisk()->exists($filePath),
        404,
    );

    return app(\App\Services\DocumentStorageService::class)->downloadResponse(
        $filePath,
        $attachmentRecord->original_filename ?: basename($filePath),
        null,
        $diskName,
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.documents.transmittal-attachment.download');

Route::get('/admin/documents/{document}/download', function (string $document) {
    $documentRecord = Document::findForRoute($document);

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    abort_unless(
        $versionRecord?->file_path
        && $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404,
    );

    return app(DocumentDownloadService::class)->download($documentRecord, $versionRecord);
})
    ->middleware(['auth', AdminMiddleware::class,  EnsureLegalStaff::class])
    ->name('admin.documents.download');

Route::get('/admin/documents/{document}/versions/{version}/preview', function (
    string $document,
    int $version
) {
    $documentRecord = Document::findForRoute($document);

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($version);

    abort_unless(
        $versionRecord->file_path &&
        $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404
    );

    return app(\App\Services\DocumentStorageService::class)->withLocalPath(
        $versionRecord->file_path,
        fn (string $localPath) => app(
            \App\Services\DocumentPreviewService::class
        )->preview($localPath),
        $versionRecord->file_disk ?: 'local',
    );
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.document.version.preview');

Route::get('/admin/documents/{document}/versions/{version}/download', function (
    string $document,
    int $version
) {
    $documentRecord = Document::findForRoute($document);
    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->findOrFail($version);

    abort_unless(
        $versionRecord->file_path &&
        $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404
    );

    return app(DocumentDownloadService::class)->download($documentRecord, $versionRecord);
})
    ->middleware(['auth', AdminMiddleware::class, EnsureLegalStaff::class])
    ->name('admin.document.version.download');


Route::post('/api/track/qr', function (Request $request) {
    $validated = $request->validate([
        'qr_token' => [
            'required',
            'string',
            'max:2048',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $value = trim((string) $value);
                $isEncryptedToken = preg_match(
                    '/^LEXTRACK-QR-1\.[A-Za-z0-9_-]+$/',
                    $value,
                ) === 1;
                $isSignedStatusUrl = filter_var($value, FILTER_VALIDATE_URL)
                    && preg_match(
                        '#/document-status/[1-9][0-9]*$#',
                        (string) parse_url($value, PHP_URL_PATH),
                    ) === 1
                    && str_contains((string) parse_url($value, PHP_URL_QUERY), 'signature=');

                if (! $isEncryptedToken && ! $isSignedStatusUrl) {
                    $fail('The QR token format is invalid.');
                }
            },
        ],
        'qr_source' => [
            'sometimes',
            'string',
            Rule::in(['camera', 'image']),
        ],
        'cf-turnstile-response' => [
            'required',
            new Turnstile(),
        ],
    ]);

    $documentId = DocumentQrToken::decode($validated['qr_token'])
        ?? DocumentQrToken::decodeSignedStatusUrl($validated['qr_token']);

    if ($documentId === null) {
        return response()
            ->json(['found' => false], 404)
            ->header('Cache-Control', 'no-store, private');
    }

    $document = Document::query()
        ->with([
            'activityLogs' => fn ($query) => $query
                ->oldest('created_at')
                ->oldest('log_id'),
        ])
        ->whereKey($documentId)
        ->first();

    if (! $document) {
        return response()
            ->json(['found' => false], 404)
            ->header('Cache-Control', 'no-store, private');
    }

    return response()
        ->json([
            'found' => true,
            'document' => [
                'tracking_number' => $document->lao_number,
                'document_type' => $document->document_type ?? 'N/A',
                'particulars' => $document->particulars,
                'date_submitted' => $document->created_at?->format('F d, Y'),
                'status' => $document->status,
                'timeline' => app(DocumentStatusTimeline::class)->build($document),
            ],
        ])
        ->header('Cache-Control', 'no-store, private');
})
    ->middleware([
        'throttle:qr-tracking',
    ])
    ->name('public.track.qr');


/*
|--------------------------------------------------------------------------
| Keep this LAST
|--------------------------------------------------------------------------
*/

Route::view('/{any}', 'home')
    ->where('any', '.*');
