<?php

namespace App\Http\Controllers\Api;
#use App\Libraries\Format;

use App\Models\User;
use App\Models\user_management\UserActivity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

abstract class RestController extends Controller
{


  // Success

  /**
   * The request has succeeded
   */
  const HTTP_OK = 200;

  /**
   * The server successfully created a new resource
   */
  const HTTP_CREATED = 201;
  const HTTP_ACCEPTED = 202;
  const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;

  /**
   * The server successfully processed the request, though no content is returned
   */
  const HTTP_NO_CONTENT = 204;
  const HTTP_RESET_CONTENT = 205;
  const HTTP_PARTIAL_CONTENT = 206;
  const HTTP_MULTI_STATUS = 207;          // RFC4918
  const HTTP_ALREADY_REPORTED = 208;      // RFC5842
  const HTTP_IM_USED = 226;               // RFC3229

  // Redirection

  const HTTP_MULTIPLE_CHOICES = 300;
  const HTTP_MOVED_PERMANENTLY = 301;
  const HTTP_FOUND = 302;
  const HTTP_SEE_OTHER = 303;

  /**
   * The resource has not been modified since the last request
   */
  const HTTP_NOT_MODIFIED = 304;
  const HTTP_USE_PROXY = 305;
  const HTTP_RESERVED = 306;
  const HTTP_TEMPORARY_REDIRECT = 307;
  const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238

  // Client Error

  /**
   * The request cannot be fulfilled due to multiple errors
   */
  const HTTP_BAD_REQUEST = 400;

  /**
   * The user is unauthorized to access the requested resource
   */
  const HTTP_UNAUTHORIZED = 401;
  const HTTP_PAYMENT_REQUIRED = 402;

  /**
   * The requested resource is unavailable at this present time
   */
  const HTTP_FORBIDDEN = 403;

  /**
   * The requested resource could not be found
   *
   * Note: This is sometimes used to mask if there was an UNAUTHORIZED (401) or
   * FORBIDDEN (403) error, for security reasons
   */
  const HTTP_NOT_FOUND = 404;

  /**
   * The request method is not supported by the following resource
   */
  const HTTP_METHOD_NOT_ALLOWED = 405;

  /**
   * The request was not acceptable
   */
  const HTTP_NOT_ACCEPTABLE = 406;
  const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
  const HTTP_REQUEST_TIMEOUT = 408;

  /**
   * The request could not be completed due to a conflict with the current state
   * of the resource
   */
  const HTTP_CONFLICT = 409;
  const HTTP_GONE = 410;
  const HTTP_LENGTH_REQUIRED = 411;
  const HTTP_PRECONDITION_FAILED = 412;
  const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
  const HTTP_REQUEST_URI_TOO_LONG = 414;
  const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
  const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
  const HTTP_EXPECTATION_FAILED = 417;
  const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
  const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
  const HTTP_LOCKED = 423;                                                      // RFC4918
  const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
  const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
  const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
  const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
  const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
  const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585

  // Server Error

  /**
   * The server encountered an unexpected error
   *
   * Note: This is a generic error message when no specific message
   * is suitable
   */
  const HTTP_INTERNAL_SERVER_ERROR = 500;

  /**
   * The server does not recognise the request method
   */
  const HTTP_NOT_IMPLEMENTED = 501;
  const HTTP_BAD_GATEWAY = 502;
  const HTTP_SERVICE_UNAVAILABLE = 503;
  const HTTP_GATEWAY_TIMEOUT = 504;
  const HTTP_VERSION_NOT_SUPPORTED = 505;
  const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
  const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
  const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
  const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
  const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;


  /**
   * HTTP status codes and their respective description
   * Note: Only the widely used HTTP status codes are used
   *
   * @var array
   * @link http://www.restapitutorial.com/httpstatuscodes.html
   */
  protected $http_status_codes = [
    self::HTTP_OK => 'OK',
    self::HTTP_CREATED => 'CREATED',
    self::HTTP_NO_CONTENT => 'NO CONTENT',
    self::HTTP_NOT_MODIFIED => 'NOT MODIFIED',
    self::HTTP_BAD_REQUEST => 'BAD REQUEST',
    self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
    self::HTTP_FORBIDDEN => 'FORBIDDEN',
    self::HTTP_NOT_FOUND => 'NOT FOUND',
    self::HTTP_METHOD_NOT_ALLOWED => 'METHOD NOT ALLOWED',
    self::HTTP_NOT_ACCEPTABLE => 'NOT ACCEPTABLE',
    self::HTTP_CONFLICT => 'CONFLICT',
    self::HTTP_INTERNAL_SERVER_ERROR => 'INTERNAL SERVER ERROR',
    self::HTTP_NOT_IMPLEMENTED => 'NOT IMPLEMENTED'
  ];



  /**
   * Constructor for the REST API
   *
   * @access public
   * @param string $config Configuration filename minus the file extension
   * e.g: my_rest.php is passed as 'my_rest'
   */
  public function __construct() {}


  public function response($data = NULL, $http_code = NULL, $continue = FALSE)
  {
    return response()->json($data, $http_code);
  }
  public function successResponse($data = NULL,)
  {
    return response()->json(array_merge(['success' => true], $data), RestController::HTTP_OK);
  }
  public function serverError($data = NULL)
  {
    return response()->json(
      array_merge(
        [
          'success' => false,
          'message' => "Server Error!"
        ],
        $data
      ),
      RestController::HTTP_INTERNAL_SERVER_ERROR
    );
  }
  public function validationError($data = [])
  {
    return response()->json(
      array_merge($data, [
        'success' => false,
        'message' => 'UNAUTHORIZED'
      ]),
      RestController::HTTP_UNAUTHORIZED
    );
  }

  public function invalidRequest($data = [])
  {
    return response()->json(
      array_merge($data, [
        'success' => false,
        'message' => 'Invalid Request',
        'message' => 'Invalid_Request'
      ]),
      RestController::HTTP_BAD_REQUEST
    );
  }

  /**
   * This is the
   *
   * @param
   * @param Request $request
   * @param string $user_type
   */
  protected function validateUserRequest(Request $request)
  {
    $u_code = $request->hasHeader('u-code') ? $request->header('u-code') : null;
    $apiKey = ($request->hasHeader('x-api-key')) ? $request->header('x-api-key') : null;
    Log::info($apiKey);
    Log::info($u_code);

    if (!$u_code || !$apiKey) return null;
    $user = User::where(['user_code' => $u_code, 'api_key' => $apiKey])->first();
    Log::info('user:' . json_encode($user));

    return $user ? $user : null;
  }


  public function AppLog($_data, $type = 'info')
  {
    Log::channel('api')->$type($_data);
  }

  public function UserActivityLog($request, $user, $data)
  {
    try {
      // Initialize Agent to get device information
      $agent = new Agent();
      $device_info = [];
      $device_info = [
        'browser' => $agent->browser(),  // Get browser name
        'ip' => $request->ip(),           // Get IP address
        'os' => $agent->platform()        // Get operating system
      ];
      // $_user_agent = $request['request_data'];
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
        'header' => json_encode($request->header()),
        'ip_address' => $request->ip()
      ];
      UserActivity::insert($log_data);
    } catch (Exception $e) {
      $this->AppLog("UserActivityLog Error: " . $e->getMessage());
    }
  }
}
