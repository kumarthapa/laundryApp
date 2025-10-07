<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProductsApiController;
use App\Http\Controllers\Api\RFIDtagDetailsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Only user login API is kept.
|
*/

// Authentication routes
Route::post('/user/login', [AuthController::class, 'userLogin'])->name('user.login');
Route::post('/user/reset-password', [AuthController::class, 'resetPassword'])->name('user.reset.password');

// Bonding Routes
// Route::post('/products/get-plan-products', [ProductsApiController::class, 'getPlanProducts'])->name('products.getPlanProducts');

// In routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes go here
    Route::post('/products/get-products', [ProductsApiController::class, 'getProducts'])
        ->name('products.getProducts');
    // New route for RFID tag details
    Route::get('/products/get-product-details-by-tag-id', [RFIDtagDetailsApiController::class, 'getProductDetailsByTagId'])
        ->name('products.getProductDetailsByTagId');

    // Updating product stage
    Route::post('/products/update-product-stage', [RFIDtagDetailsApiController::class, 'updateProductStage'])
        ->name('products.updateProductStage');

    // Route for fetching product stages and status
    Route::post('/products/get-stages-and-status', [ProductsApiController::class, 'getStagesAndStatus'])
        ->name('products.getStagesAndStatus');

    // Dashboard summary and stages routes
    Route::get('dashboard/summary', [DashboardApiController::class, 'summary'])->name('dashboard.getSummary');
    Route::get('dashboard/stages', [DashboardApiController::class, 'stages'])->name('dashboard.getStages');
    Route::get('dashboard/recent-activities', [DashboardApiController::class, 'recentActivities'])->name('dashboard.getRecentActivities');

    // Plan bondig products
    Route::post('/products/get-plan-products', [ProductsApiController::class, 'getPlanProducts'])
        ->name('products.getPlanProducts');
    // NEW: Update QA Code
    Route::post('/products/update-qa-code', [ProductsApiController::class, 'updateQaCode'])
        ->name('products.updateQaCode');

    // Updating product name
    Route::post('/products/update-product-name', [RFIDtagDetailsApiController::class, 'updateProductName'])
        ->name('products.updateProductName');

});
