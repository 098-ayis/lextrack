<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\GoogleAuthController;


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


Route::get('/admin/documents/{document}/file', function (Document $document) {
    abort_unless(auth()->check(), 403);

    abort_unless(
        $document->file_path &&
        Storage::disk('local')->exists($document->file_path),
        404
    );

    return response()->file(
        Storage::disk('local')->path($document->file_path)
    );
})
    ->middleware('auth')
    ->name('admin.documents.file');


/*
|--------------------------------------------------------------------------
| Keep this LAST
|--------------------------------------------------------------------------
*/

Route::view('/{any}', 'home')
    ->where('any', '.*');