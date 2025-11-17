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

        $device = DeviceRegistration::where('device_id', $request->device_id)->first();

        if (! $device) {
            return response()->json([
                'update_required' => false,
                'message' => 'Device not registered',
            ]);
        }

        if ($device->is_update_required == 1 && $request->current_version_code < $device->latest_version_code) {
            $mustUpdate = true;
        } else {
            $mustUpdate = false;
        }

        if ($mustUpdate) {
            return response()->json([
                'update_required' => true,
                'latest_version_code' => (int) $device->latest_version_code,
                'apk_url' => 'https://apps.galla.ai/sleepcompany/assets/apk/rfidapp/app-release.apk',
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

        $device = DeviceRegistration::where('device_id', $request->device_id)->first();

        if (! $device) {
            Log::warning('Attempt to mark update on non-existent device', ['device_id' => $request->device_id]);

            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ]);
        }

        $device->is_update_required = 0;
        $device->latest_version_code = $request->updated_version;
        $device->last_updated_at = Carbon::now();
        $device->save();

        return response()->json([
            'success' => true,
            'message' => 'Device updated successfully',
        ]);
    }
}
