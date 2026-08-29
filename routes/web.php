<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
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

    abort_unless(
        $documentRecord->file_path &&
        Storage::disk('local')->exists($documentRecord->file_path),
        404
    );

    return response()->file(
        Storage::disk('local')->path($documentRecord->file_path)
    );

})
    ->middleware('auth')
    ->name('client.document.preview');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');


/*
|--------------------------------------------------------------------------
| Keep this LAST
|--------------------------------------------------------------------------
*/

Route::view('/{any}', 'home')
    ->where('any', '.*');
