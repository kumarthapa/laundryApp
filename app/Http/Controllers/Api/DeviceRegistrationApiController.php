<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\device_registration\DeviceRegistration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeviceRegistrationApiController extends Controller
{
    /**
     * Verify license key only (no device/serial checks).
     * Request: license_key (required)
     */
    public function verifyLicense(Request $request)
    {
        Log::info('verifyLicense request '.json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'device_id' => 'required|string', // now required
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 422);
        }

        $licenseKey = $request->input('license_key');
        $deviceId = $request->input('device_id');

        $record = DeviceRegistration::where('license_key', $licenseKey)->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid license key',
            ], 404);
        }

        // Check status
        if ($record->status === 'INACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'License is inactive',
                'status' => 'INACTIVE',
            ], 403);
        }

        // Check expiry
        if ($record->end_date && Carbon::now()->gt(Carbon::parse($record->end_date))) {
            if ($record->status !== 'EXPIRE') {
                $record->status = 'EXPIRE';
                $record->save();
            }

            return response()->json([
                'success' => false,
                'message' => 'License expired',
                'status' => 'EXPIRE',
                'end_date' => $record->end_date,
            ], 403);
        }

        // --- Bind device if not yet assigned ---
        if (empty($record->device_id)) {
            $record->device_id = $deviceId;
            $record->save();
            Log::info("License {$licenseKey} assigned to device {$deviceId}");
        } elseif ($record->device_id !== $deviceId) {
            // Already assigned to a different device
            return response()->json([
                'success' => false,
                'message' => 'License already assigned to another device',
                'device_id' => $record->device_id,
            ], 403);
        }

        // Success
        return response()->json([
            'success' => true,
            'message' => 'License valid for this device',
            'data' => [
                'license_key' => $record->license_key,
                'device_id' => $record->device_id,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'status' => $record->status,
            ],
        ], 200);
    }

    public function createLicense(Request $request)
    {
        Log::info('createLicense request: '.json_encode($request->all()));
        // exit;
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:150',
            'serial_number' => 'nullable|string|max:150',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->input('device_id');
        $serialNumber = $request->input('serial_number', null);
        $endDateInput = $request->input('end_date', null);

        // Avoid creating duplicate registration for same device_id
        if (DeviceRegistration::where('device_id', $deviceId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Device already registered or license already exists for this device_id',
            ], 409);
        }

        DB::beginTransaction();
        try {
            $now = Carbon::now();

            // Default end_date to now + 1 year if not provided
            if ($endDateInput) {
                $endDate = Carbon::parse($endDateInput)->toDateString();
            } else {
                $endDate = $now->copy()->addYear()->toDateString();
            }

            // start date = now
            $startDate = $now->toDateString();

            // Force status to INACTIVE as per your requirement
            $status = 'INACTIVE';

            // Generate license key (same format as web controller)
            $licenseKey = $this->generateLicenseKeyForApi($deviceId);

            $createData = [
                'device_id' => $deviceId,
                'serial_number' => $serialNumber,
                'license_key' => $licenseKey,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $model = DeviceRegistration::create($createData);

            if (! $model) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Create failed'], 500);
            }

            // optional activity logging (if you have the method available)
            if (method_exists($this, 'UserActivityLog')) {
                $this->UserActivityLog($request, [
                    'module' => 'device_registration',
                    'activity_type' => 'create',
                    'message' => "Create device : {$deviceId}",
                    'application' => 'api',
                    'data' => [
                        'device_id' => $deviceId,
                        'license_key' => $licenseKey,
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'License created successfully',
                'data' => [
                    'license_key' => $licenseKey,
                    'end_date' => $endDate,
                    'status' => $status,
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating license: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate license key logic reused for API.
     * Format matches your DeviceRegistrationController::generateLicenseKey implementation.
     */
    protected function generateLicenseKeyForApi(string $deviceId): string
    {
        // 1. Sanitize and extract a short prefix from deviceId (first 4 alphanumeric chars)
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $deviceId), 0, 4)) ?: 'DEV';

        // 2. Encode current timestamp in base36 for compactness
        $timestampBlock = strtoupper(base_convert(time(), 10, 36));

        // 3. Generate 3 random blocks (4 hex chars each)
        $rand1 = strtoupper(bin2hex(random_bytes(2)));
        $rand2 = strtoupper(bin2hex(random_bytes(2)));
        $rand3 = strtoupper(bin2hex(random_bytes(2)));

        // 4. Combine parts
        $licenseKey = "{$prefix}-{$timestampBlock}-{$rand1}-{$rand2}-{$rand3}";

        // Quick uniqueness retry loop (few attempts)
        $attempt = 0;
        while (DeviceRegistration::where('license_key', $licenseKey)->exists() && $attempt < 5) {
            $attempt++;
            $rand1 = strtoupper(bin2hex(random_bytes(2)));
            $rand2 = strtoupper(bin2hex(random_bytes(2)));
            $rand3 = strtoupper(bin2hex(random_bytes(2)));
            $timestampBlock = strtoupper(base_convert(time(), 10, 36));
            $licenseKey = "{$prefix}-{$timestampBlock}-{$rand1}-{$rand2}-{$rand3}";
        }

        // Fallback: longer random string if still collides (very unlikely)
        if (DeviceRegistration::where('license_key', $licenseKey)->exists()) {
            $licenseKey = $prefix.'-'.strtoupper(bin2hex(random_bytes(8)));
        }

        return $licenseKey;
    }

    public function checkDevice(Request $request)
    {
        Log::info('checkDevice request: '.json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->input('device_id');

        $record = DeviceRegistration::where('device_id', $deviceId)->first();

        if (! $record) {
            // No license exists for this device
            return response()->json([
                'success' => false,
                'message' => 'No license found for this device',
            ], 404);
        }

        // If the record has an end_date and it's past, mark EXPIRE if not already
        if ($record->end_date && Carbon::now()->gt(Carbon::parse($record->end_date))) {
            if ($record->status !== 'EXPIRE') {
                $record->status = 'EXPIRE';
                $record->save();
            }

            return response()->json([
                'success' => false,
                'message' => 'License expired',
                'status' => 'EXPIRE',
                'data' => [
                    'license_key' => $record->license_key,
                    'end_date' => $record->end_date,
                ],
            ], 403);
        }

        // Return license info (status could be ACTIVE or INACTIVE)
        return response()->json([
            'success' => true,
            'message' => 'Device license found',
            'status' => $record->status,
            'data' => [
                'license_key' => $record->license_key,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
            ],
        ], 200);
    }
}
