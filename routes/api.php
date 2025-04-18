<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ApartmentController;
use App\Http\Controllers\API\FavoriteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// API Health Check Routes
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0',
        'environment' => app()->environment(),
        'server_time' => now()->toDateTimeString(),
    ]);
});

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail']);

// Public Apartment Routes
Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{apartment}', [ApartmentController::class, 'show']);
Route::get('/featured-apartments', [ApartmentController::class, 'featured']);
Route::get('/search-apartments', [ApartmentController::class, 'search']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile Routes
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Protected Apartment Routes
    Route::post('/apartments', [ApartmentController::class, 'store']);
    Route::put('/apartments/{apartment}', [ApartmentController::class, 'update']);
    Route::delete('/apartments/{apartment}', [ApartmentController::class, 'destroy']);
    Route::get('/user/apartments', [ApartmentController::class, 'userApartments']);

    // Favorites Routes
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{apartment}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/{apartment}/check', [FavoriteController::class, 'check']);
    Route::post('/favorites/{listing}/toggle', [FavoriteController::class, 'toggle']);
});