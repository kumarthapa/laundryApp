<?php

namespace App\Models;

use App\Models\user_management\UserActivity;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class AppModel extends Model
{

  public function __construct()
  {
    parent::__construct();
  }

  public function UserActivityLog($request, $data)
  {
    try {
      $user = Auth::user();
      // Initialize Agent to get device information
      $agent = new Agent();
      $device_info = [];
      // Retrieve the headers from the request
      $headers = $request->headers->all();
      $device_info = [
        'browser' => $agent->browser(),  // Get browser name
        'ip' => $request->ip(),           // Get IP address
        'os' => $agent->platform()        // Get operating system
      ];
      $_user_agent = $request->header('User-Agent');
      $log_data = [
        'usercode' => $user->user_code,
        'datetime' => date('Y-m-d H:i:s'),
        'module' => $data['module'] ?? '',
        'activity_type' => $data['activity_type'] ?? '',
        'message' => $data['message'] ?? '',
        'application' => $data['application'] ?? '',
        'user_agent' => json_encode($_user_agent),
        'device' => json_encode($device_info),
        'data' => json_encode($data['data']),
        'header' => json_encode($headers),
        'ip_address' => $request->ip()
      ];
      UserActivity::insert($log_data);
    } catch (Exception $e) {
      Log::error("UserActivityLog Error: " . $e->getMessage());
    }
  }
}
