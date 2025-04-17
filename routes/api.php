<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApartmentController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Authentication Routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Public Routes
Route::get('apartments', [ApartmentController::class, 'index']);
Route::get('apartments/{apartment}', [ApartmentController::class, 'show']);
Route::get('featured-apartments', [ApartmentController::class, 'featured']);
Route::get('search-apartments', [ApartmentController::class, 'search']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Routes
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);

    // Apartment Routes (Protected Operations)
    Route::post('apartments', [ApartmentController::class, 'store']);
    Route::put('apartments/{apartment}', [ApartmentController::class, 'update']);
    Route::delete('apartments/{apartment}', [ApartmentController::class, 'destroy']);
    
    // Favorite Routes
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites', [FavoriteController::class, 'store']);
    Route::delete('favorites/{apartment}', [FavoriteController::class, 'destroy']);
    Route::get('favorites/{apartment}/check', [FavoriteController::class, 'check']);

    // User's Apartments
    Route::get('user/apartments', [ApartmentController::class, 'userApartments']);
}); 