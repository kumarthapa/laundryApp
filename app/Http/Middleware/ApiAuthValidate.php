<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthValidate
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Get the current route name
    $routeName = $request->route()->getName();

    // Get the current route action
    $routeAction = $request->route()->getActionName();

    // You can also get other route information like parameters, methods, etc.
    $routeParameters = $request->route()->parameters();

    // Log::info("routeName: " . $routeName);
    // Log::info("routeAction: " . json_encode($routeAction));
    // Log::info("routeParameters: " . json_encode($routeParameters));

    $apiKey = $request->header('X-API-Key');
    $u_code = $request->header('u-code');

    if (!$apiKey || !$u_code) {
      return response()->json(['error' => 'API key is missing'], 401);
    }

    // Check if the API key exists in the user table
    $user = User::where([['api_key', $apiKey], ['user_code', $u_code]])->first();
    if (!$user) {
      return response()->json(['message' => 'NOT AUTTHORIZED', 'error' => 'NOT_AUTTHORIZED'], 401);
    }

    // You can attach the authenticated user instance to the request
    // to access it in your controller
    $request->user = $user;

    return $next($request);
  }
}
