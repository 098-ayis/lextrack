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

Route::get('/admin/documents/export', [DocumentExportController::class, '__invoke'])
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.documents.export');

Route::get('/document-status/{document}', function (int $document) {
    $documentRecord = Document::query()
        ->with(['user', 'type', 'actionType'])
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
        ->where('user_id', auth()->id())
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
        ->where('user_id', auth()->id())
        ->firstOrFail();

    abort_unless(
        $documentRecord->file_path &&
        Storage::disk('local')->exists($documentRecord->file_path),
        404
    );

    return Storage::disk('local')->download(
        $documentRecord->file_path,
        basename($documentRecord->file_path)
    );
})
    ->middleware('auth')
    ->name('client.document.download');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');


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

    return response()->download($disk->path($filePath), basename($filePath));
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


/*
|--------------------------------------------------------------------------
| Keep this LAST
|--------------------------------------------------------------------------
*/

Route::view('/{any}', 'home')
    ->where('any', '.*');
