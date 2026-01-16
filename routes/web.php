<?php

use Illuminate\Support\Facades\Route;
Use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Http\Request;

// GET route to open reset password page from email
Route::get('/reset-password', function (Request $request) {
    return view('reset-password', [
        'email' => $request->email,
        'token' => $request->token,
    ]);
});

// POST route to submit the form and show success message
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/', function () {
    return view('welcome');
});
