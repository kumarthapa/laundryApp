<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\device_registration\DeviceRegistration;
use App\Models\location\Location;
use App\Models\User;
use App\Models\user_management\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * User login with license_key validation.
     *
     * Request payload:
     *  - license_key (required)
     *  - username (required)
     *  - password (required)
     *  - device_id (optional) // if you want server to validate binding
     */
    public function userLogin(Request $request)
    {
        // WARNING: avoid logging raw passwords in production. This is helpful for debug; remove in prod.
        Log::info('User login attempt', $request->except('password')); // do not log password

        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'license_key' => 'required|string|max:255',
                'username' => 'required|alpha_dash|max:255',
                'password' => 'required|string|min:4|max:255',
                'device_id' => 'nullable|string|max:150',
            ]);

            if ($validator->fails()) {
                Log::info('Login validation failed', ['errors' => $validator->errors(), 'ip' => $request->ip()]);

                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $licenseKey = trim($request->input('license_key'));
            $username = $request->input('username');
            $password = $request->input('password');
            $deviceId = $request->input('device_id', null);

            // 1) Validate license record (public endpoint style rule)
            $license = DeviceRegistration::where('license_key', $licenseKey)->first();
            if (! $license) {
                Log::info('License lookup failed', ['license_key' => $licenseKey, 'ip' => $request->ip()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid license key',
                ], Response::HTTP_NOT_FOUND);
            }

            // 2) Check license status
            if ($license->status === 'INACTIVE') {
                return response()->json([
                    'success' => false,
                    'message' => 'License is inactive',
                    'status' => 'INACTIVE',
                ], Response::HTTP_FORBIDDEN);
            }

            // 3) Check expiry and mark EXPIRE if necessary
            if ($license->end_date && Carbon::now()->gt(Carbon::parse($license->end_date))) {
                if ($license->status !== 'EXPIRE') {
                    $license->status = 'EXPIRE';
                    $license->save();
                }

                return response()->json([
                    'success' => false,
                    'message' => 'License expired',
                    'status' => 'EXPIRE',
                    'end_date' => $license->end_date,
                ], Response::HTTP_FORBIDDEN);
            }

            // 4) Optional: device binding
            if (! empty($deviceId) && ! empty($license->device_id) && $license->device_id !== $deviceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'License is assigned to a different device',
                ], Response::HTTP_FORBIDDEN);
            }

            // 5) Validate user credentials
            $user = User::where('username', $username)->first();
            if (! $user || ! Hash::check($password, $user->password)) {
                Log::warning('Login attempt failed', ['username' => $username, 'ip' => $request->ip()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // 6) Check user active
            if ($user->status !== 'Active') {
                Log::info('Inactive user login blocked', ['user_id' => $user->id]);

                return response()->json([
                    'success' => false,
                    'message' => 'User account is not active',
                ], Response::HTTP_FORBIDDEN);
            }

            // 7) Prevent super-admin on mobile (policy)
            if (! empty($user->is_super_admin) && $user->is_super_admin) {
                Log::info('Super admin login attempt blocked', ['user_id' => $user->id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid account details!',
                ], Response::HTTP_FORBIDDEN);
            }

            if (empty($user->location_id) || $user->location_id == 0) {
                Log::info('Location Is Not Assigned!', ['user_id' => $user->id, 'location_id' => $user->location_id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid account details!',
                ], Response::HTTP_FORBIDDEN);
            }

            // 8) Attach license device id to user (audit)
            $user->device_id = $license->device_id;
            $user->save();

            // 9) Create Sanctum token (do NOT log token)
            $tokenResult = $user->createToken('api-token');

            // 10) Build response
            $user_role = Role::select('role_id', 'role_name', 'role_code')->find($user->role_id);
            $locationName = optional(Location::find($user->location_id))->location_name;

            // 11) Get Working Stages ---------- START -------------<<
            $working_stages = $user->working_stage ? json_decode($user->working_stage, true) : null;
            //  Get Working Stages ---------- END ------------- >>

            $data = [
                'user' => [
                    'name' => $user->fullname,
                    'email' => $user->email,
                    'user_code' => $user->user_code,
                    'location_id' => $user->location_id,
                    'location_name' => $locationName,
                    'working_stages' => $working_stages ?? [],
                    'role' => $user_role,
                    'api_key' => $user->api_key,
                ],
                'permissions' => $user->getUserPermissions(),
                'token' => $tokenResult->plainTextToken,
            ];
            Log::info('User login response data', ['user_id' => $user->id, 'data' => $data]);
            Log::info('User login successful', ['user_id' => $user->id, 'license_key' => $licenseKey]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $data,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('userLogin exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'newpass' => 'required|string|min:8|max:255',
        ]);
        if ($validator->fails()) {
            Log::info('Password reset validation failed', ['errors' => $validator->errors()]);

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            Log::warning('Password reset attempted for non-existent email', ['email' => $request->email]);

            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $user->password = Hash::make($request->newpass);
        $user->save();

        Log::info('Password changed successfully', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ], Response::HTTP_OK);
    }
}
