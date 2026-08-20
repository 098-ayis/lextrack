<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Requests;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Auth;

Route::view('/ai-test', 'ai-test');
Route::post('/ask-ai', [AIController::class, 'ask']);

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/api/user', function(Request $request) {
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
        'message' => 'Logged out successfully'
    ]);
})->middleware('auth');

/*
Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/api/admin/users', ...);

    Route::get('/api/admin/documents', ...);

    Route::delete('/api/admin/users/{id}', ...);

});
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::view('/{any}', 'home')
    ->where('any', '.*');



