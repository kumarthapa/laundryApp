<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use App\Models\settings\Configsetting;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */


  public function boot(): void
  {
    // Load configuration only if the table exists
    if (Schema::hasTable('config_settings')) {
      $configSettings = \App\Models\settings\Configsetting::all();
      foreach ($configSettings as $setting) {
        config([$setting->key => $setting->value]);
      }
    }

    // Your existing Blade directives
    Blade::directive('checkPermission', function ($expression) {
      list($module, $permission) = explode(',', $expression);
      if (\App\Helpers\UtilityHelper::checkModulePermissions(trim($module), trim($permission))) {
        return "<?php ";
      } else {
        return "<?php /*";
      }
    });

    Blade::directive('endCheckPermission', function () {
      return "*/?>";
});
}
}