<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

// Brands - Marka CRUD əməliyyatları
Route::apiResource('brands', BrandController::class);

// Cars - Maşın CRUD əməliyyatları
Route::apiResource('cars', CarController::class);

// Car Images - Şəkil əməliyyatları
Route::post('cars/{car}/images', [CarController::class, 'addImages']);
Route::delete('cars/{car}/images/{image}', [CarController::class, 'deleteImage']);
Route::put('cars/{car}/images/{image}/primary', [CarController::class, 'setPrimaryImage']);

// Əlavə filter route-ları
Route::get('cars/brand/{brandId}', function($brandId) {
    $cars = \App\Models\Car::where('brand_id', $brandId)
        ->with(['brand', 'images'])
        ->paginate(12);
    
    return response()->json([
        'success' => true,
        'data' => $cars
    ]);
});

Route::get('cars/featured', function() {
    $cars = \App\Models\Car::featured()
        ->with(['brand', 'images'])
        ->limit(6)
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $cars
    ]);
});