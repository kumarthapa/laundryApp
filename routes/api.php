<?php

use App\Http\Controllers\Api\AppUpdateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DeviceRegistrationApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\ProductsApiController;
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

    // Dashboard summary and stages routes
    Route::get('dashboard/summary', [DashboardApiController::class, 'summary'])->name('dashboard.getSummary');
    Route::get('dashboard/stages', [DashboardApiController::class, 'stages'])->name('dashboard.getStages');
    Route::get('dashboard/recent-activities', [DashboardApiController::class, 'recentActivities'])->name('dashboard.getRecentActivities');

    // ------------- New Routes for Inventory Management Start ------------- //

    // Inventory: Tag Mapping
    Route::post('inventory/tag-mapping', [InventoryApiController::class, 'tagMapping'])
        ->name('inventory.tagMapping');

    // Inventory: Record Stock Movement (inward/outward/washing etc.)
    Route::post('inventory/record-stock-movement', [InventoryApiController::class, 'recordStockMovement'])
        ->name('inventory.recordStockMovement');

    // Inventory: Get tag + product + activity history by EPC
    Route::get('inventory/tag-details/{epc}', [InventoryApiController::class, 'tagDetailsByEpc'])
        ->name('inventory.tagDetailsByEpc');

    // ------------- New Routes for Inventory Management End------------- //

    // Route::get('inventory/tag-details/{epc}', [InventoryApiController::class, 'tagDetailsByEpc']);

});
// routes/api.php
// Public route — anyone can call with license key
Route::post('device/verify-license', [DeviceRegistrationApiController::class, 'verifyLicense'])
    ->name('device.verifyLicense');
// Public route — create a new license for a device
Route::post('device/create-license', [DeviceRegistrationApiController::class, 'createLicense'])
    ->name('device.createLicense');

// Public endpoint: check device license by device_id (Android ID)
Route::post('device/check', [DeviceRegistrationApiController::class, 'checkDevice'])
    ->name('device.check');

Route::post('device/check-update', [AppUpdateController::class, 'checkUpdate']);
Route::post('device/mark-updated', [AppUpdateController::class, 'markUpdated']);
