<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class CaptureRequestDetails
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    $agent = new Agent();
    $ip = $request->ip();
    $location = Location::get($ip);

    $requestDetails = [
      'ip' => $ip,
      'location' => $location,
      'device' => $agent->device(),
      'platform' => $agent->platform(),
      'browser' => $agent->browser(),
    ];

    // You can log these details or store them in the database
    // Log::info('Request Details:::>', $requestDetails);

    $request->merge(['request_data' => $requestDetails]);

    return $next($request);
  }
}
