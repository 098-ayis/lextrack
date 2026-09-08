<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Middleware\AdminMiddleware;
use App\Services\DocumentDownloadService;
use Spatie\Honeypot\Honeypot;
use Spatie\Honeypot\ProtectAgainstSpam;


Route::view('/ai-test', 'ai-test');

Route::post('/ask-ai', [AIController::class, 'ask']);

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

    return view('documents.public-status', [
        'document' => $documentRecord,
    ]);
})
    ->middleware('signed')
    ->name('documents.public-status');


/*
|--------------------------------------------------------------------------
| Client document preview
|--------------------------------------------------------------------------
*/

Route::get('/client/document-preview/{document}', function ($document) {

    $documentRecord = Document::query()
        ->where('document_id', $document)
        ->where(function ($query) {
            $query
                ->where('user_id', auth()->id())
                ->orWhereHas(
                    'documentRequests',
                    fn ($requestQuery) => $requestQuery->where(
                        'user_id',
                        auth()->id()
                    )->where('status', 'accepted')
                );
        })
        ->firstOrFail();

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

    return response()->file(
        $disk->path($filePath)
    );

})
    ->middleware('auth')
    ->name('client.document.preview');

Route::get('/client/document-download/{document}', function (int $document) {
    $documentRecord = Document::query()
        ->where('document_id', $document)
        ->where(function ($query) {
            $query
                ->where('user_id', auth()->id())
                ->orWhereHas(
                    'documentRequests',
                    fn ($requestQuery) => $requestQuery->where(
                        'user_id',
                        auth()->id()
                    )->where('status', 'accepted')
                );
        })
        ->firstOrFail();

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

    return app(DocumentDownloadService::class)->download($documentRecord, $versionRecord);
})
    ->middleware('auth')
    ->name('client.document.download');


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

Route::get('/admin/documents/{document}/preview', function (int $document) {

    $documentRecord = Document::findOrFail($document);
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

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' .
            basename($path) .
            '"',
    ]);
})
    ->middleware('auth')
    ->name('admin.documents.preview');

Route::get('/admin/documents/{document}/download', function (int $document) {
    $documentRecord = Document::findOrFail($document);

    $versionRecord = DocumentVersion::query()
        ->where('document_id', $document)
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
    int $document,
    int $version
) {
    $versionRecord = DocumentVersion::query()
        ->where('document_id', $document)
        ->findOrFail($version);

    abort_unless(
        $versionRecord->file_path &&
        $versionRecord->storageDisk()->exists($versionRecord->file_path),
        404
    );

    $path = $versionRecord->storageDisk()->path($versionRecord->file_path);

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' .
            basename($versionRecord->file_path) .
            '"',
    ]);
})
    ->middleware('auth')
    ->name('admin.document.version.preview');


Route::get('/api/honeypot', function (Honeypot $honeypot) {
    return response()->json($honeypot->toArray());
})->name('public.honeypot');


Route::post('/api/track', function (Request $request) {

    $validated = $request->validate([
        'tracking_number' => [
            'required',
            'string',
            'max:50',
            'regex:/^[A-Za-z0-9-]+$/',
        ],
    ]);

    $trackingNumber = strtoupper(
        trim($validated['tracking_number'])
    );

    $document = Document::query()
        ->where('lao_number', $trackingNumber)
        ->first();

    if (! $document) {
        return response()->json([
            'found' => false,
        ], 404);
    }

    return response()->json([
        'found' => true,

        'document' => [
            'tracking_number' => $document->lao_number,

            'document_type' =>
                $document->document_type ?? 'N/A',

            'particulars' =>
                $document->particulars,

            'date_submitted' =>
                $document->created_at?->format('F d, Y'),

            'status' =>
                $document->status,
        ],
    ]);
})
->middleware([
    ProtectAgainstSpam::class,
    'throttle:10,1',
])
->name('public.track.document');


/*
|--------------------------------------------------------------------------
| Keep this LAST
|--------------------------------------------------------------------------
*/

Route::view('/{any}', 'home')
    ->where('any', '.*');
