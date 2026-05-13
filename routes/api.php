<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::post('/email/verification-notification', [UserController::class, 'resendVerification']);

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/trash', [UserController::class, 'trash']);
        Route::get('/toggle/{id}', [UserController::class, 'toggleStatus']);
        Route::delete('/delete/{id}', [UserController::class, 'destroy']);
        Route::get('/restore/{id}', [UserController::class, 'restore']);
        Route::get('/export', [UserController::class, 'export']);
    });
});