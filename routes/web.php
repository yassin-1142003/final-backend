<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AdTypeController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\ListingController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\SavedSearchController;

Route::get('/', function () {
    return view('welcome');
});


// Test route
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Healthcheck endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0',
        'environment' => app()->environment(),
        'server_time' => now()->toDateTimeString(),
    ]);
});

// Serve Postman collection
Route::get('/postman', function () {
    $path = base_path('real-estate-api.postman_collection.json');
    if (file_exists($path)) {
        return response()->file($path);
    }
    return response()->json(['error' => 'Postman collection not found'], 404);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail']);

// Social login
Route::get('/login/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/login/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/login/facebook', [AuthController::class, 'redirectToFacebook']);
Route::get('/login/facebook/callback', [AuthController::class, 'handleFacebookCallback']);

// Public listing routes
Route::get('/listings/featured', [ListingController::class, 'featured']);
Route::get('/listings/search', [ListingController::class, 'search']);
Route::get('/listings', [ListingController::class, 'index']);
Route::get('/listings/{listing}', [ListingController::class, 'show']);
Route::get('/ad-types', [AdTypeController::class, 'index']);
Route::get('/apartments', [UserController::class, 'getFilteredApartments']);

// City and governorate listing stats
Route::get('/locations/stats', [ListingController::class, 'locationStats']);
Route::get('/property-types/stats', [ListingController::class, 'propertyTypeStats']);

// Public comments routes
Route::get('/listings/{listing}/comments', [CommentController::class, 'index']);
Route::get('/listings/{listing}/comments/{comment}', [CommentController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/refresh-token', [AuthController::class, 'refresh']);

    // User profile
    Route::get('/profile', [UserController::class, 'getProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/change-password', [UserController::class, 'changePassword']);

    // Listings
    Route::apiResource('listings', ListingController::class);
    Route::get('/my-listings', [ListingController::class, 'myListings']);
    Route::post('/listings/{listing}/images', [ListingController::class, 'uploadImages']);
    Route::delete('/listings/{listing}/images/{image}', [ListingController::class, 'deleteImage']);

    // Favorites
    Route::apiResource('favorites', FavoriteController::class);
    Route::post('/favorites/{listing}/toggle', [FavoriteController::class, 'toggle']);

    // Comments
    Route::apiResource('comments', CommentController::class);
    Route::get('/listings/{listing}/comments', [CommentController::class, 'listingComments']);

    // Bookings
    Route::apiResource('bookings', BookingController::class);
    Route::post('/listings/{listing}/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);

    // Payments
    Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment']);
    Route::get('/payments/history', [PaymentController::class, 'history']);

    // Saved searches
    Route::apiResource('saved-searches', SavedSearchController::class);

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/listings', [AdminController::class, 'listings']);
        Route::put('/listings/{listing}/approve', [AdminController::class, 'approveListing']);
        Route::put('/listings/{listing}/reject', [AdminController::class, 'rejectListing']);
        Route::get('/payments', [AdminController::class, 'payments']);
        Route::get('/statistics', [AdminController::class, 'statistics']);
    });
});