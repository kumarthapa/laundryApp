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
        Log::info('--- CHECK UPDATE CALLED ---', [
            'incoming_request' => $request->all(),
        ]);

        // Validate request
        $request->validate([
            'device_id' => 'required|string|max:150',
            'current_version_code' => 'required|integer',
        ]);

        $deviceId = $request->input('device_id');
        $currentVersion = (int) $request->input('current_version_code');

        Log::info('Validated request', [
            'device_id' => $deviceId,
            'current_version_code' => $currentVersion,
        ]);

        // Check device exists
        $device = DeviceRegistration::where('device_id', $deviceId)->first();

        if (! $device) {
            Log::warning('Device not found', [
                'device_id' => $deviceId,
            ]);

            return response()->json([
                'update_required' => false,
                'message' => 'Device not registered',
            ], 404);
        }

        Log::info('Device found', [
            'device_id' => $deviceId,
            'registered_version_code' => $device->latest_version_code,
            'is_update_required_flag' => $device->is_update_required,
        ]);

        // Only rely on flag (your requirement)
        $mustUpdate = ($device->is_update_required == 1);

        if ($mustUpdate) {

            $apkUrl = 'https://apps.galla.ai/sleepcompany/assets/apk/rfidapp/galla-rfid-app.apk';

            Log::info('Update REQUIRED', [
                'device_id' => $deviceId,
                'device_current_version' => $currentVersion,
                'server_latest_version' => $device->latest_version_code,
                'apk_url_sent_to_device' => $apkUrl,
            ]);

            return response()->json([
                'update_required' => true,
                'latest_version_code' => (int) $device->latest_version_code,
                'apk_url' => $apkUrl,
                'message' => 'A new update is available. Please update the app.',
            ]);
        }

        // No update needed
        Log::info('Device is up to date', [
            'device_id' => $deviceId,
            'current_version' => $currentVersion,
        ]);

        return response()->json([
            'update_required' => false,
            'message' => 'Your app is up to date.',
        ]);
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
