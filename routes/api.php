<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\UserController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']); // GET /api/users?search=
    Route::get('/trash', [UserController::class, 'trash']); // GET trashed users
    Route::get('/toggle/{id}', [UserController::class, 'toggleStatus']); // Toggle status
    Route::delete('/delete/{id}', [UserController::class, 'destroy']); // Soft delete
    Route::get('/restore/{id}', [UserController::class, 'restore']); // Restore user
    Route::get('/export', [UserController::class, 'export']); // Export users
});