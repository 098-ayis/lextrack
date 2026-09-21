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
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Middleware\AdminMiddleware;
use App\Services\DocumentDownloadService;
use App\Services\DocumentQrToken;
use App\Services\DocumentStatusTimeline;
use Spatie\Honeypot\Honeypot;
use Spatie\Honeypot\ProtectAgainstSpam;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;
Route::post('/chatbot/message', [
    ChatbotController::class,
    'reply',
])->middleware(['auth', 'throttle:10,1'])->name('chatbot.message');

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/login', 'home')->name('login');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/api/user', function (Request $request) {
    return response()->json([
        'authenticated' => true,
        'user' => $request->user(),
    ]);
})->middleware('auth');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
        'message' => 'Logged out successfully',
    ]);
})->middleware('auth');

Route::get('/admin/users/export', [UserExportController::class, '__invoke'])
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.users.export');

Route::get('/admin/document-export', [DocumentExportController::class, '__invoke'])
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.documents.export');

Route::get('/admin/reports/monthly', App\Http\Controllers\MonthlyReportController::class)
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.reports.monthly');

Route::get('/document-qr/{document}', App\Http\Controllers\DocumentQrCodeController::class)
    ->middleware('signed')
    ->name('documents.qr');

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
            ->where('status', 'accepted')
            ->exists(),
        404
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $disk = Storage::disk('local');
    $filePath = $versionRecord?->file_path;

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless(
        $filePath &&
        $disk->exists($filePath),
        404
    );

    return app(\App\Services\DocumentPreviewService::class)->preview($disk->path($filePath));

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
            ->where('status', 'accepted')
            ->exists(),
        404
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $disk = Storage::disk('local');
    $filePath = $versionRecord?->file_path;

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless($filePath && $disk->exists($filePath), 404);

    return app(\App\Services\DocumentPreviewService::class)->thumbnail($disk->path($filePath));
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
            ->where('status', 'accepted')
            ->exists(),
        404
    );

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $disk = Storage::disk('local');
    $filePath = $versionRecord?->file_path;

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless($filePath && $disk->exists($filePath), 404);

    $fileName = basename($filePath);

    return response()->download(
        $disk->path($filePath),
        $fileName,
        [
            'Content-Type' => $disk->mimeType($filePath)
                ?: 'application/octet-stream',
        ]
    );
})
    ->middleware('auth')
    ->name('client.document.download');

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
    $disk = Storage::disk($attachment->disk ?: 'local');

    if (! $disk->exists($filePath) && $attachment->disk !== 'public') {
        $disk = Storage::disk('public');
    }

    abort_unless($disk->exists($filePath), 404);

    $fileName = basename($attachment->original_name ?: $filePath);
    $mimeType = $attachment->mime_type
        ?: $disk->mimeType($filePath)
        ?: 'application/octet-stream';
    $quotedFileName = addcslashes($fileName, "\\\"");

    if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf') {
        return response()->file($disk->path($filePath), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $quotedFileName . '"',
        ]);
    }

    return response()->download(
        $disk->path($filePath),
        $fileName,
        ['Content-Type' => $mimeType]
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
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.document.temp-preview');

Route::get('/admin/documents/{document}/preview', function (string $document) {

    $documentRecord = Document::findForRoute($document);
    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $disk = Storage::disk('local');
    $filePath = $versionRecord?->file_path;

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless(
        $filePath &&
        $disk->exists($filePath),
        404
    );

    $path = $disk->path($filePath);

    return app(\App\Services\DocumentPreviewService::class)->preview($path);
})
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.documents.preview');

Route::get('/admin/documents/{document}/thumbnail', function (string $document) {
    $documentRecord = Document::findForRoute($document);
    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $disk = Storage::disk('local');
    $filePath = $versionRecord?->file_path;

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless($filePath && $disk->exists($filePath), 404);

    return app(\App\Services\DocumentPreviewService::class)->thumbnail($disk->path($filePath));
})
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.documents.thumbnail');

Route::get('/admin/documents/{document}/transmittal-preview', function (string $document) {
    $documentRecord = Document::findForRoute($document);
    $filePath = $documentRecord->transmittal;

    $disk = Storage::disk('local');

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless(
        $filePath && $disk->exists($filePath),
        404
    );

    return app(\App\Services\DocumentPreviewService::class)->preview(
        $disk->path($filePath)
    );
})
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.documents.transmittal.preview');

Route::get('/admin/documents/{document}/transmittal-download', function (string $document) {
    $documentRecord = Document::findForRoute($document);
    $filePath = $documentRecord->transmittal;

    $disk = Storage::disk('local');

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless(
        $filePath && $disk->exists($filePath),
        404
    );

    return $disk->download($filePath, basename($filePath));
})
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.documents.transmittal.download');

Route::get('/admin/documents/{document}/download', function (string $document) {
    $documentRecord = Document::findForRoute($document);

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $documentRecord->document_id)
        ->latest('created_at')
        ->latest('version_id')
        ->first();

    $disk = Storage::disk('local');
    $filePath = $versionRecord?->file_path;

    if ($filePath && ! $disk->exists($filePath)) {
        $disk = Storage::disk('public');
    }

    abort_unless(
        $filePath &&
        $disk->exists($filePath),
        404
    );

    return app(DocumentDownloadService::class)->download($documentRecord, $versionRecord);
})
    ->middleware(['auth', AdminMiddleware::class])
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

    $path = $versionRecord->storageDisk()->path($versionRecord->file_path);

    return app(\App\Services\DocumentPreviewService::class)->preview($path);
})
    ->middleware(['auth', AdminMiddleware::class])
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
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.document.version.download');

Route::get('/admin/document-temp-preview/{file}', function (string $file) {
    abort_unless(
        preg_match('/^[a-f0-9]{32}\.pdf$/', $file) === 1,
        404
    );

    $path = storage_path('app/private/temp-previews/' . $file);

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $file . '"',
    ]);
})
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.document.temp-preview');


Route::get('/api/honeypot', function (Honeypot $honeypot) {
    return response()->json($honeypot->toArray());
})->name('public.honeypot');


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
            'exclude_unless:qr_source,image',
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
        ProtectAgainstSpam::class,
        'throttle:10,1',
    ])
    ->name('public.track.qr');


/*
|--------------------------------------------------------------------------
| Keep this LAST
|--------------------------------------------------------------------------
*/

Route::view('/{any}', 'home')
    ->where('any', '.*');
