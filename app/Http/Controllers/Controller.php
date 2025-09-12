<?php

namespace App\Http\Controllers;

use App\Models\user_management\UserActivity;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
  use AuthorizesRequests, ValidatesRequests;

  public function successResponse($data)
  {
    return response()->json(array_merge(['success' => true], $data), 200);
  }
  public function errorResponse($data, $status_code = 500)
  {
    return response()->json(array_merge(
      $data,
      [
        'success' => false,
        'message' => 'Server Error!'
      ]
    ), $status_code);
  }

  public function inValidRequestResponse($data)
  {
    return response()->json(array_merge(
      $data,
      [
        'success' => false,
        'message' => 'Invalid Operation'
      ]
    ), 400);
  }

  public function UserActivityLog($request, $data)
  {
    try {
      $user = Auth::user();
      $headers = [];
      $device_info = [];

      // Check if $request is an object before accessing its properties
      if (is_object($request)) {
        $agent = new Agent();
        $headers = $request->headers->all();
        $device_info = [
          'browser' => $agent->browser(),
          'ip' => $request->ip(),
          'os' => $agent->platform()
        ];
        $_user_agent = $request->header('User-Agent');
        $ip_address = $request->ip();
      } else {
        $_user_agent = null;
        $ip_address = null;
      }

      $log_data = [
        'usercode' => $user->user_code ?? '',
        'datetime' => now(),
        'module' => $data['module'] ?? '',
        'activity_type' => $data['activity_type'] ?? '',
        'message' => $data['message'] ?? '',
        'application' => $data['application'] ?? '',
        'user_agent' => json_encode($_user_agent),
        'device' => json_encode($device_info),
        'data' => json_encode($data['data']),
        'header' => json_encode($headers),
        'ip_address' => $ip_address
      ];

      UserActivity::insert($log_data);
    } catch (Exception $e) {
      Log::error("UserActivityLog Error: " . $e->getMessage());
    }
  }


  public function AppLog($_data, $type = 'info')
  {
    Log::channel('api')->$type($_data);
  }

  public function getErrorMessage($e)
  {
    if (isset($e) && !empty($e)) {
      return "Error: " . $e->getMessage() . "\n File : " . $e->getFile() . "\n Line : " . $e->getLine();
    }
    return '';
  }

  protected function xss_clean($str, $is_image = FALSE)
  {
    return $str;
  }
}