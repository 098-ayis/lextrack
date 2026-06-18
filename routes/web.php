<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Requests;

Route::get('/', function () {
    return view('home');
});

//Named routes
Route::get('/test', function () {
    return "This is a test!";
})->name('testpage');


//login routes
Route::get('/login', function () {
    return view('login');
})->name('login');

Route::prefix("login")->group(function () {
    Route::get("/admin", function () {
        return view('adminLogin');
    });

    Route::get("/client", function () {
        return view('clientLogin');
    });
});

//
Route::post("/formsubmitted", function( Request $request) {

$requesr->validate([
    'email' => 'required|email',
    'password' => 'required|min:8'
]);
    $email = $request->input('email');
    $password = $request->input('password');
    
    return "Email: $email";
});