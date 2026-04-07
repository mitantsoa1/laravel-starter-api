<?php

use App\Http\Controllers\Api\Auth\AuthController;
// use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\HomeController; // Import HomeController
use App\Http\Controllers\Api\UserController;
use App\Models\User;
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
Route::post('/verification/resend', [AuthController::class, 'resendVerificationNotification']);
Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    $user = User::findOrFail($request->route('id'));

    if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
        return response()->json(["message" => "Lien de vérification invalide."], 403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));
    }

    if ($request->wantsJson()) {
        return response()->json(['message' => 'Votre adresse e-mail a été vérifiée avec succès.']);
    }

    // Redirect to frontend login with a success parameter (fallback for browser clicks)
    return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?verified=1');
})->middleware(['signed'])->name('verification.verify');
Route::get('/alba-homes', [HomeController::class, 'albaHomesAllData']); // Added Alba Homes route

Route::get('/auth/google/url', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'handleGoogleCallback']);
Route::post('/auth/social/exchange', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'exchangeCodeForToken']);


// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'profile']);
    Route::delete('/profile', [AuthController::class, 'deleteAccount']);
    Route::post('/profile/password', [AuthController::class, 'changePassword']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']); // Added refresh route

    // User Resource Routes
    Route::apiResource('users', UserController::class);

    // Umami

    Route::prefix('analytics/umami')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\UmamiController::class, 'getStats']);
        Route::get('/pageviews', [\App\Http\Controllers\Api\UmamiController::class, 'getPageViews']);
        Route::get('/metrics', [\App\Http\Controllers\Api\UmamiController::class, 'getMetrics']);
        Route::get('/metrics/expanded', [\App\Http\Controllers\Api\UmamiController::class, 'getExpandedMetrics']);
        Route::get('/events', [\App\Http\Controllers\Api\UmamiController::class, 'getEvents']);
        Route::get('/events/series', [\App\Http\Controllers\Api\UmamiController::class, 'getEventSeries']);
        Route::get('/active', [\App\Http\Controllers\Api\UmamiController::class, 'getActive']);
    });

    // Dashboard Statistics
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
});
