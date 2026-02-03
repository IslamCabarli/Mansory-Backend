<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CarController;

// Test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API işləyir!',
        'version' => '1.0'
    ]);
});

// ============================================
// AUTH ROUTES (Public)
// ============================================
Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected auth routes
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });
});

// ============================================
// PUBLIC ROUTES 
// ============================================

// Brands - public
Route::get('brands', [BrandController::class, 'index']);
Route::get('brands/{brand}', [BrandController::class, 'show']);

// Cars - public
Route::get('cars', [CarController::class, 'index']);
Route::get('cars/{car}', [CarController::class, 'show']);

// Featured cars
Route::get('cars-featured', function() {
    $cars = \App\Models\Car::featured()
        ->with(['brand', 'images'])
        ->limit(6)
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $cars
    ]);
});

// Cars by brand
Route::get('brands/{brandId}/cars', function($brandId) {
    $cars = \App\Models\Car::where('brand_id', $brandId)
        ->with(['brand', 'images'])
        ->paginate(12);
    
    return response()->json([
        'success' => true,
        'data' => $cars
    ]);
});
// ============================================
// PROTECTED ROUTES 
// ============================================
Route::middleware('auth:api', 'admin')->group(function () {
    
    // Brands - CRUD (Admin only)
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);
    
    // Cars - CRUD (Admin only)
    Route::post('cars', [CarController::class, 'store']);
    Route::put('cars/{car}', [CarController::class, 'update']);
    Route::delete('cars/{car}', [CarController::class, 'destroy']);
    
    // Car Images (Admin only)
    Route::post('cars/{car}/images', [CarController::class, 'addImages']);
    Route::delete('cars/{car}/images/{image}', [CarController::class, 'deleteImage']);
    Route::put('cars/{car}/images/{image}/primary', [CarController::class, 'setPrimaryImage']);
});