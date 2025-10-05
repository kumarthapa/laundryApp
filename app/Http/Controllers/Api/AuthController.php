<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\user_management\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function userLogin(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'username' => 'required|alpha_num|max:255',
            'password' => 'required|string|min:4|max:255',
        ]);
        if ($validator->fails()) {
            Log::info('Login validation failed', ['errors' => $validator->errors()]);

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $username = $request->input('username');
        $password = $request->input('password');

        // Find user
        $user = User::where('username', $username)->first();

        // Validate user existence and password
        if (! $user || ! Hash::check($password, $user->password)) {
            Log::warning('Login attempt failed', ['username' => $username]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is active
        if ($user->status !== 'Active') {
            Log::info('Inactive user login blocked', ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'User account is not active',
            ], Response::HTTP_FORBIDDEN);
        }

        // Create Sanctum personal access token
        $tokenResult = $user->createToken('api-token');

        // Get role information
        $user_role = Role::select('role_id', 'role_name', 'role_code')->find($user->role_id);

        // Prepare response data
        $data = [
            'user' => [
                'name' => $user->fullname,
                'email' => $user->email,
                'user_code' => $user->user_code,
                'role' => $user_role,
                'api_key' => $user->api_key,
            ],
            'permissions' => $user->getUserPermissions(),
            'token' => $tokenResult->plainTextToken,
        ];
        Log::info('User login successful', ['user_id' => $user->id]);
        Log::info('Response data: '.json_encode($data));
        Log::info('Sanctum token generated', ['token' => $tokenResult->plainTextToken]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $data,
        ], Response::HTTP_OK);
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
