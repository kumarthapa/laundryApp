<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\device_registration\DeviceRegistrationController;
use App\Http\Controllers\user_management\Users;
use Illuminate\Support\Facades\Route;

$controller_path = 'App\Http\Controllers';

Route::get('/auth/login', [AuthController::class, 'index'])->name('auth-login');
Route::post('/user-login', [AuthController::class, 'userLogin'])->name('user-login');
Route::get('/user-logout', [AuthController::class, 'userLogout'])->name('user-logout');
Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('auth-reset-password-basic');

// Route::group(['middleware' => ['auth']], function () {
Route::group(['middleware' => ['auth', 'prevent-back-history']], function () {

    $controller_path = 'App\Http\Controllers';

    /**
     * * This Routes are for Employess
     */

    // Main Page Route
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', $controller_path.'\dashboard\DashboardController@index')->name('dashboard');
    Route::get('/dashboard/metrics', $controller_path.'\dashboard\DashboardController@metrics')->name('metrics'); // JSON for charts (polling)
    Route::get('/dashboard/list', $controller_path.'\dashboard\DashboardController@list')->name('dashboard.list');
    Route::get('/dashboard/export-products', $controller_path.'\dashboard\DashboardController@exportProducts')->name('dashboard.exportProducts');

    // Roles And Permissions
    Route::get('/roles', $controller_path.'\user_management\Roles@index')->name('roles')->middleware('permission:roles,view.roles');
    Route::get('/roles/list', $controller_path.'\user_management\Roles@list')->name('roles.list');
    Route::post('/roles/save/{id?}', $controller_path.'\user_management\Roles@save')->name('roles.save')->middleware('permission:roles,view.roles');
    Route::get('/roles/view/{id?}/{details?}', $controller_path.'\user_management\Roles@view')->name('roles.view')->middleware('permission:roles,view.roles');
    Route::get('/roles/create/{id?}', $controller_path.'\user_management\Roles@create')->name('roles.create')->middleware('permission:roles,view.roles');
    Route::post('/roles/delete/{id?}', $controller_path.'\user_management\Roles@delete')->name('roles.delete')->middleware('permission:roles,create.roles');
    Route::post('/roles/saveModulePermissions/{id?}', $controller_path.'\user_management\Roles@saveModulePermissions')->name('roles.saveModulePermissions')->middleware('permission:roles,create.roles');

    Route::get('/users', $controller_path.'\user_management\Users@index')->name('users')->middleware('permission:users,view.users');
    Route::get('/users/view/{id?}', $controller_path.'\user_management\Users@view')->name('users.view')->middleware('permission:users,view.users');
    Route::get('/users/activity/{id?}', $controller_path.'\user_management\Users@userActivity')->name('users.activity')->middleware('permission:users,view.users');
    Route::get('/users/list', $controller_path.'\user_management\Users@list')->name('users.list');
    Route::get('/users/create/{id?}', $controller_path.'\user_management\Users@create')->name('users.create')->middleware('permission:users,create.users');
    Route::get('/users/edit/{user_code?}', $controller_path.'\user_management\Users@edit_user')->name('users.edit')->middleware('permission:users,edit.users');
    Route::post('/users/save/{id?}', $controller_path.'\user_management\Users@save')->name('users.save')->middleware('permission:users,create.users');
    Route::post('/users/delete/{id?}', $controller_path.'\user_management\Users@delete')->name('users.delete')->middleware('permission:users,create.users');
    Route::get('/profile/{user_code?}', [Users::class, 'profile'])->name('profile');

    Route::post('/users/changePassword/{id?}', $controller_path.'\user_management\Users@changePassword')->name('users.changePassword')->middleware('permission:users,create.users');
    Route::post('/users/sendotp', $controller_path.'\user_management\Users@sendotp')->name('users.sendotp')->middleware('permission:users,create.users');
    Route::post('/users/activityLogs/{id?}', $controller_path.'\user_management\Users@userActivityLogs')->name('users.activityLogs')->middleware('permission:users,view.users');

    // Routes for Config Settings Controller
    Route::get('/settings', $controller_path.'\settings\SettingsController@index')->name('settings')->middleware('permission:config_settings,view.config_settings');
    Route::get('/settings/list', $controller_path.'\settings\SettingsController@list')->name('settings.list')->middleware('permission:config_settings,view.settings');
    Route::post('/settings/save', $controller_path.'\settings\SettingsController@save')->name('settings.save')->middleware('permission:config_settings,create.config_settings');
    Route::get('/settings/save/locations', $controller_path.'\settings\SettingsController@saveLocations')->name('settings.save-locations')->middleware('permission:config_settings,create.config_settings');
    Route::post('/settings/save_config', $controller_path.'\settings\SettingsController@save_config')->name('settings.save_config')->middleware('permission:config_settings,create.config_settings');
    Route::get('/settings/view/{id?}', $controller_path.'\settings\SettingsController@view')->name('settings.view')->middleware('permission:config_settings,create.config_settings');
    Route::get('/settings/create/{id?}', $controller_path.'\settings\SettingsController@create')->name('settings.create')->middleware('permission:config_settings,create.config_settings');
    Route::post('/settings/delete/{id?}', $controller_path.'\settings\SettingsController@delete')->name('settings.delete')->middleware('permission:config_settings,delete.config_settings');
    Route::get('/settings/getconfigValuesByConfigkey', $controller_path.'\settings\SettingsController@getconfigValuesByConfigkey')->name('settings.getconfigValuesByConfigkey');
    Route::post('/settings/uploadRequiredDocuments/{code?}', $controller_path.'\settings\SettingsController@uploadRequiredDocuments')->name('settings.uploadRequiredDocuments');

    // ================================= PRODUCTS PLANNING ROUTES ================================ //

    Route::get('/products', $controller_path.'\products\ProductsController@index')->name('products')->middleware('permission:products,view.products');
    Route::get('/products/list', $controller_path.'\products\ProductsController@list')->name('products.list')->middleware('permission:products,view.products');
    Route::get('/products/create/{id?}', $controller_path.'\products\ProductsController@create')->name('create.products')->middleware('permission:products,create.products');
    Route::get('/products/edit/{id?}', $controller_path.'\products\ProductsController@edit')->name('edit.products')->middleware('permission:products,edit.products');
    Route::get('/products/view/{code?}', $controller_path.'\products\ProductsController@view')->name('view.products')->middleware('permission:products,view.products');
    Route::post('/products/bulk-product-upload', $controller_path.'\products\ProductsController@bulkProductUpload')->name('products.bulkProductUpload')->middleware('permission:products,create.products');
    Route::get('/products/productImportFormat', $controller_path.'\products\ProductsController@productImportFormat')->name('products.productImportFormat');
    Route::get('/products/export-products', $controller_path.'\products\ProductsController@exportProducts')->name('products.exportProducts');
    Route::get('/products/export-products-stagewise', $controller_path.'\products\ProductsController@exportProductsStageWise')->name('products.exportProductsStageWise');

    // AJAX routes
    Route::post('/products/save/{id?}', $controller_path.'\products\ProductsController@save')->name('products.save')->middleware('permission:products,create.products');
    Route::post('/products/delete/{id?}', $controller_path.'\products\ProductsController@delete')->name('delete.products')->middleware('permission:products,delete.products');
    Route::post('/products/setlocationid', $controller_path.'\products\ProductsController@setlocationid')->name('products.setlocationid');

    // ================================= BONDING PRODUCTS ROUTES ================================ //

    Route::get('/bonding', $controller_path.'\products\BondingPlanProductController@index')->name('bonding')->middleware('permission:bonding,view.bonding');
    Route::get('/bonding/list', $controller_path.'\products\BondingPlanProductController@list')->name('bonding.list')->middleware('permission:bonding,view.bonding');
    Route::get('/bonding/create/{id?}', $controller_path.'\products\BondingPlanProductController@create')->name('create.bonding')->middleware('permission:bonding,create.bonding');
    Route::get('/bonding/edit/{id?}', $controller_path.'\products\BondingPlanProductController@edit')->name('edit.bonding')->middleware('permission:bonding,edit.bonding');
    Route::get('/bonding/view/{code?}', $controller_path.'\products\BondingPlanProductController@view')->name('view.bonding')->middleware('permission:bonding,view.bonding');
    Route::get('/bonding/export-bonding', $controller_path.'\products\BondingPlanProductController@exportBonding')->name('bonding.exportBonding');

    // AJAX routes
    Route::post('/bonding/save/{id?}', $controller_path.'\products\BondingPlanProductController@save')->name('bonding.save')->middleware('permission:bonding,create.bonding');
    Route::post('/bonding/delete/{id?}', $controller_path.'\products\BondingPlanProductController@delete')->name('delete.bonding')->middleware('permission:bonding,delete.bonding');

    // bulkBondingPlanUpload routes
    Route::get('/bonding/bondingPlanImportFormat', $controller_path.'\products\BondingPlanProductController@bondingPlanImportFormat')->name('bonding.bondingPlanImportFormat');
    Route::post('/bonding/bulkBondingPlanUpload', $controller_path.'\products\BondingPlanProductController@bulkBondingPlanUpload')->name('bonding.bulkBondingPlanUpload')->middleware('permission:bonding,create.bonding');

    // ================================= ALL TYPES OF REPORTS ROUTES ================================ //

    // Route::get('/reports', $controller_path . '\reports\ReportsController@index')->name('reports');
    // Route::post('/reports/list', $controller_path . '\reports\ReportsController@list')->name('reports.list');

    Route::get('/reports', $controller_path.'\reports\ReportsController@index')->name('reports')->middleware('permission:reports,view.reports');
    Route::post('/reports/list', $controller_path.'\reports\ReportsController@list')->name('reports.list')->middleware('permission:reports,view.reports');
    Route::post('/reports/export', $controller_path.'\reports\ReportsController@exportReport')->name('reports.export')->middleware('permission:reports,view.reports');
    Route::post('/reports/defect_export', $controller_path.'\reports\ReportsController@exportDefectReport')->name('reports.defect_export')->middleware('permission:reports,view.reports');

    // ================================= User Locations ROUTES ================================ //
    Route::get('/locations', $controller_path.'\locations\LocationController@index')->name('locations')->middleware('permission:locations,locations.view');
    Route::get('/locations/list', $controller_path.'\locations\LocationController@list')->name('locations.list')->middleware('permission:locations,locations.view');
    Route::get('/locations/view/{id?}', $controller_path.'\locations\LocationController@view')->name('locations.view')->middleware('permission:locations,locations.view');
    Route::get('/locations/create', $controller_path.'\locations\LocationController@create')->name('locations.create')->middleware('permission:locations,locations.create');
    Route::get('/locations/edit/{id?}', $controller_path.'\locations\LocationController@edit')->name('locations.edit')->middleware('permission:locations,locations.edit');
    Route::post('/locations/save/{id?}', $controller_path.'\locations\LocationController@save')->name('locations.save')->middleware('permission:locations,locations.create');
    Route::post('/locations/delete/{id?}', $controller_path.'\locations\LocationController@delete')->name('locations.delete')->middleware('permission:locations,locations.delete');
    Route::get('/locations/getLocationOverview', $controller_path.'\locations\LocationController@getLocationOverview')->name('locations.getLocationOverview')->middleware('permission:locations,locations.view');

    // ================================= Device registration ROUTES ================================ //
    Route::get('/galla-device-registration', [DeviceRegistrationController::class, 'index'])->name('device_registration');
    Route::post('/galla-device-registration/list', [DeviceRegistrationController::class, 'list'])->name('device_registration.list');
    Route::get('/galla-device-registration/view/{id?}', [DeviceRegistrationController::class, 'view'])->name('device_registration.view');
    Route::get('/galla-device-registration/create', [DeviceRegistrationController::class, 'create'])->name('device_registration.create');
    Route::get('/galla-device-registration/edit/{id?}', [DeviceRegistrationController::class, 'edit'])->name('device_registration.edit');
    Route::post('/galla-device-registration/save/{id?}', [DeviceRegistrationController::class, 'save'])->name('device_registration.save');
    Route::post('/galla-device-registration/delete/{id?}', [DeviceRegistrationController::class, 'delete'])->name('device_registration.delete');
});
