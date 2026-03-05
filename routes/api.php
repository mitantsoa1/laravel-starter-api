<?php

use App\Http\Controllers\Api\Auth\AuthController;
// use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\HomeController; // Import HomeController
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::get('/alba-homes', [HomeController::class, 'albaHomes']); // Added Alba Homes route

Route::get('/auth/google/url', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'handleGoogleCallback']);
Route::post('/auth/social/exchange', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'exchangeCodeForToken']);


// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']); // Added refresh route

    // User Resource Routes
    Route::apiResource('users', UserController::class);
});
