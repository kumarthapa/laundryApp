<?php

namespace App\Http\Middleware;

use App\Helpers\UtilityHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
  public function handle($request, Closure $next, $module, $permission)
  {
    $utilityHelper = new UtilityHelper();
    if (!$utilityHelper->checkModulePermissions($module, $permission)) {
      return response()->view('content.auth.not-authorised', [], 403);
    }

    return $next($request);
  }
}
