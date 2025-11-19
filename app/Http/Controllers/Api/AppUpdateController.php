<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\device_registration\DeviceRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AppUpdateController extends Controller
{
    /**
     * Check if device needs update
     */
    public function checkUpdate(Request $request)
    {
        Log::info('App update check', ['request' => $request->all()]);

        $request->validate([
            'device_id' => 'required|string|max:150',
            'current_version_code' => 'required|integer',
        ]);

        $deviceId = $request->input('device_id');
        $currentVersion = (int) $request->input('current_version_code');

        $device = DeviceRegistration::where('device_id', $deviceId)->first();

        if (! $device) {
            // 404 might be more appropriate; keeping 200 JSON for compatibility if you prefer
            return response()->json([
                'update_required' => false,
                'message' => 'Device not registered',
            ], 404);
        }

        // Log the current version the device reported (useful for debugging)
        Log::info('Device update check', ['device_id' => $deviceId, 'current_version_code' => $currentVersion]);

        // Only rely on the `is_update_required` flag (as you requested)
        $mustUpdate = ($device->is_update_required == 1);

        if ($mustUpdate) {
            // Build the APK URL using asset() so it's consistent with app URL configuration.
            // If your APK is located in public/sleepcompany/assets/apk/rfidapp/galla-rfid-app.apk
            // asset() will produce the correct absolute URL.
            $apkRelativePath = 'sleepcompany/assets/apk/rfidapp/galla-rfid-app.apk';
            $apkUrl = asset($apkRelativePath);

            // Optionally check file existence if APK lives in public/...
            $apkFileExists = file_exists(public_path($apkRelativePath));
            if (! $apkFileExists) {
                Log::warning('APK file not found on server: '.public_path($apkRelativePath));
                // You may still return update_required true but leave apk_url empty or return an error.
            }

            return response()->json([
                'update_required' => true,
                'latest_version_code' => (int) $device->latest_version_code,
                'apk_url' => $apkUrl,
                'message' => 'A new update is available. Please update the app.',
            ]);
        } else {
            return response()->json([
                'update_required' => false,
                'message' => 'Your app is up to date.',
            ]);
        }
    }

    /**
     * Mark device as updated
     */
    public function markUpdated(Request $request)
    {
        Log::info('Marking device as updated', ['request' => $request->all()]);

        $request->validate([
            'device_id' => 'required|string|max:150',
            'updated_version' => 'required|integer',
        ]);

        $deviceId = $request->input('device_id');
        $updatedVersion = (int) $request->input('updated_version');

        $device = DeviceRegistration::where('device_id', $deviceId)->first();

        if (! $device) {
            Log::warning('Attempt to mark update on non-existent device', ['device_id' => $deviceId]);

            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        // Update device record
        $device->is_update_required = 0;
        $device->latest_version_code = $updatedVersion;
        $device->last_updated_at = Carbon::now();
        $device->save();

        Log::info('Device marked updated', ['device_id' => $deviceId, 'updated_version' => $updatedVersion]);

        return response()->json([
            'success' => true,
            'message' => 'Device updated successfully',
        ]);
    }
}
