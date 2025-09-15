<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductsApiController;
use App\Http\Controllers\Api\RFIDtagDetailsApiController;

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

//Bonding Routes
//Route::post('/products/get-plan-products', [ProductsApiController::class, 'getPlanProducts'])->name('products.getPlanProducts');

// In routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes go here
    Route::post('/products/get-plan-products', [ProductsApiController::class, 'getPlanProducts'])
         ->name('products.getPlanProducts');
// New route for RFID tag details
Route::get('/products/get-product-details-by-tag-id', [RFIDtagDetailsApiController::class, 'getProductDetailsByTagId'])
    ->name('products.getProductDetailsByTagId');

    // Updating product stage
Route::post('/products/update-product-stage', [RFIDtagDetailsApiController::class, 'updateProductStage'])
    ->name('products.updateProductStage');

    // Route for fetching product stages and status
Route::post('/products/get-stages-and-status', [ProductsApiController::class, 'getStagesAndStatus'])
    ->name('products.getStagesAndStatus');
});



