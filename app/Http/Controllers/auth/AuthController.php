<?php

namespace App\Http\Controllers\auth;

use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
  public function index()
  
  {
   
    if (Auth::user()) redirect(route('dashboard'));
    return view('content.auth.auth-login');
  }

  public function userLogin(Request $request)
  {

    try {
      // Validate the request
      $request->validate([
        'username' => 'required|string',
        'password' => 'required',
      ]);
      $credentials = $request->only('username', 'password');
      if (Auth::attempt($credentials)) {
        // Authentication passed...
        $user = Auth::user();
        // Check if the user is not active
        if ($user->status !== 'Active') {
          Auth::logout(); // Log out the user if they are not active
          return response()->json([
            'message' => 'Your account is not active. Please contact support.',
            'success' => false,
          ], 403); // 403 Forbidden response
        }

        $response = [
          'message' => 'Login successful',
          'success' => true,
          'userdetails' => $user,
        ];
        //remember me checked
        $rememberMe = $request->input('rememberMe', 0);
        Session::put('rememberMe', $rememberMe);
        // Set session data
        Session::put('user', $user);
        Session::save();
        // Insert user activity ---- START -----------
        $this->UserActivityLog(
          $request,
          [
            'module' => 'users',
            'activity_type' => 'login',
            'message' => 'Users: ' . $user->fullame . ' Logged in',
            'application' => 'web',
            'data' => null
          ]
        );
        // Insert user activity ---- END -----------


        $response['return_url'] = route('dashboard');


        return response()->json($response, 200);
      }

      // Authentication failed...
      return response()->json([
        'message' => 'Invalid credentials!',
        'success' => false,
      ], 401);
    } catch (Exception $exception) {
      // Log the error
      Log::error("Error: " . $exception->getMessage());

      return response()->json([
        'message' => 'An error occurred during login',
        'success' => false,
        'error' => $exception->getMessage(),
      ], 500);
    }
  }

  public function userLogout(Request $request)
  {
    try {
      $user = Auth::user();
      // Insert user activity ---- START -----------
      $this->UserActivityLog(
        $request,
        [
          'module' => 'users',
          'activity_type' => 'logout',
          'message' => 'Users: ' . $user->fullame . ' Logout',
          'application' => 'web',
          'data' => null
        ]
      );
      // Insert user activity ---- END -----------
      // Log out the user
      Auth::logout();

      // Clear the session data
      if (!Session::get('rememberMe')) {
        Session::flush();
      }
      // Redirect to the login page
      return redirect('auth/login')->with('message', 'You have been successfully logged out.');
    } catch (Exception $exception) {
      // Log the error
      Log::error("Logout Error: " . $exception->getMessage());

      // Redirect to the login page with an error message
      return redirect('auth/login')->with('error', 'An error occurred during logout. Please try again.');
    }
  }

}