<?php

namespace App\Helpers;

use App\Models\settings\Configsetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
#use CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentExpiryNotification;
use Carbon\Carbon;
use App\Models\user_management\Permission;
use App\Models\user_management\GrantsPermission;
use App\Models\user_management\Role;
use File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use App\Models\user_management\UsersModel;
use App\Models\customers\CustomerRateCards;
use App\Models\employees\EmployeeAttendance;
use App\Models\Trips\Tours;
use App\Helpers\ConfigHelper;

class UtilityHelper
{

  public static function CheckModulePermissions($module_id = '', $permission_id = '')
  {
    // Check if user is authenticated
    $role_id = '';
    if (Auth::check()) {
      $user = Auth::user();
      $role_id = $user->role_id;
    }

    // No role or module provided means permission denied
    if (!$role_id || !$module_id) {
      return false;
    }

    // Fetch grants for this role and module
    $result = GrantsPermission::where([
      ['role_id', $role_id],
      ['module_id', $module_id]
    ])->get();

    if ($result->isEmpty()) {
      return false;
    }

    // If no specific permission requested, just check if any grant exists
    if (empty($permission_id)) {
      return true;
    }

    // Check each grant's permission_id JSON array for the requested permission
    foreach ($result as $details) {
      $per_ids = json_decode($details->permission_id, true);
      if (is_array($per_ids) && in_array($permission_id, $per_ids)) {
        return true;
      }
    }

    // Permission not found in grants
    return false;
  }



  public static function getLoginUserInfo()
  {
    // Check if user is authenticated
    $result =  false;
    if (Auth::check()) {
      // User is logged in, retrieve user details
      $user = Auth::user();
      if ($user) {
        $result = $user;
      }
      return $result;
    } else {
      return $result;
    }
  }

  public static function getSelectedPermissionInfo($permission_id = '', $module_id = '')
  {
    // Get module and permission
    $permissions_info = Permission::select('*')
      ->where('permission_id', $permission_id)
      ->where('module_id', $module_id)
      ->get();
    $module_permission_array = [];
    //print_r($permissions_info); exit;
    foreach ($permissions_info as $per) {
      return $per->permission_name;
    }
    // return $module_permission;
  }
  public static function getUserRoleInfo($role_id = '')
  {
    $result =  false;
    if (Auth::check() && !$role_id) {
      $user = Auth::user();
      if ($user->role_id) {
        $role_info = Role::find($user->role_id);
        return $role_info;
      }
      return $result;
    } else {
      if ($role_id) {
        $role_info = Role::find($role_id);
        return $role_info;
      }
      return $result;
    }

    return $result;
  }


  /**
   * Method to get random string of specific length. Can also add prefix if passed over params.
   *
   * @param int $length The length of the random string to generate.
   * @param string|null $prefix The prefix to prepend to the generated string.
   * @param bool $has_numbers Whether to include numbers in the generated string.
   * @param bool $has_capitals Whether to include capital letters in the generated string.
   * @param bool $only_capitals Whether to use only capital letters in the generated string.
   * @return string The generated random string.
   */
  public static function generateRandomString($length = 6, $prefix = null, $has_numbers = false, $has_capitals = true, $only_capitals = false)
  {
    $characters = '';
    // $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    if ($only_capitals) {
      $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      if ($has_numbers) {
        $characters .= '0123456789';
      }
    } else {
      if ($has_numbers) {
        $characters .= '0123456789';
      }
      if ($has_capitals) {
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      }
      $characters .= 'abcdefghijklmnopqrstuvwxyz';
    }
    if (empty($characters)) {
      // Fallback to a default set if no characters are selected
      $characters = 'abcdefghijklmnopqrstuvwxyz';
    }
    $randomString = '';
    if ($prefix) {
      $randomString .= $prefix;
    }
    for ($i = 0; $i < $length; $i++) {
      $index = rand(0, strlen($characters) - 1);
      $randomString .= $characters[$index];
    }
    return $randomString;
  }

  public static function generateCustomCode($name)
  {
    // Normalize the name: remove special characters and convert to uppercase
    $normalized = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
    $normalized = strtoupper(trim($normalized));

    // Split the name into words
    $words = explode(' ', $normalized);

    // Initialize the short code
    $shortCode = '';


    $shortCode .= strtoupper(substr($words[0], 0, 2));
    if (count($words) > 1) {
      // If multiple words, take the first two letters of the first word

      // Add the first letter of each subsequent word
      for ($i = 1; $i < count($words); $i++) {
        $shortCode .= strtoupper(substr($words[$i], 0, 2));
      }
    } else {
      // If single word, take the last two characters
      $firstWord = $words[0];
      $shortCode .= strtoupper(substr($firstWord, -2));
    }

    // Ensure the code is exactly 4 characters long
    return substr($shortCode, 0, 4);
  }

  /**
   * Method to upload images to AWS S3 bubket.
   *
   * @param array $images
   * @param string $folder  main folder in bucket
   * @param string $path    sub path to save file
   * @param string $prefix  prefix for file name
   */
  public static function uploadImages($images = null, $folder = '', $path = null, $prefix = '')
  {
    if ($images == null)
      return null;
    $file_path = [];
    foreach ($images as $index => $image) {
      $fileName = $path;
      $fileName .=  UtilityHelper::generateRandomString(4, $prefix, true, false, false) . '.' . $image->getClientOriginalExtension();
      $full_path =  $folder . $fileName;
      $file_saved = S3Helper::uploadFile($image, $full_path);
      $file_path[] = $file_saved;
      Log::info("file saved " . $file_saved . " -- ");
    }
    return $file_path;
  }
  public static function uploadImagesWithOriginalName($images = null, $folder = '', $path = null, $prefix = '')
  {
    if ($images == null) {
      return null;
    }
    $file_paths = [];
    foreach ($images as $index => $image) {
      // Get the original file name and extension
      $originalName = $image->getClientOriginalName(); // e.g., "Final draft Transport & Service Agreement.docx"
      $extension = $image->getClientOriginalExtension(); // e.g., "docx"
      // Clean or modify original name (optional but recommended to avoid S3 path issues)
      $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME)); // safe file name
      $fileName = $cleanName;
      // Optionally add a prefix or make unique
      // Add random string to ensure uniqueness
      $fileName .= '_' . uniqid() . '.' . $extension;
      // Full path to save in S3
      $full_path = rtrim($folder, '/') . '/' . $fileName;
      // Upload to S3
      $file_saved = S3Helper::uploadFile($image, $full_path);
      $file_paths[] = $file_saved;

      Log::info("File saved to S3: " . $file_saved);
    }
    return $file_paths;
  }


  public static function getDocsTypes()
  {
    $documents = Configsetting::where('key', 'documents')->first();
    $docs_details = [];
    if (isset($documents->value)) {
      foreach (json_decode($documents->value, true) as $docs) {
        $docs_details[] = $docs;
      }
    }
    return $docs_details;
    
  }


  /**
   * @param string $type this is to select the type of masters to fetch the docs
   *
   * @return array  list of docs with config.
   */
  public static function getDocsFields($config_key)
  {
    if (!$config_key) return [];
    $docs_types = Configsetting::where('key', $config_key)->first();
    $docs_required = isset($docs_types->value) ? json_decode($docs_types->value, true) : null;


    if ($docs_required) {
      // Create a map of docs_type to is_required
      $docs_types_in_required = [];
      foreach ($docs_required as $doc) {
        $docs_types_in_required[$doc['docs_type']] = $doc['is_required'];
      }

      // Filter the docs based on the docs_type in required
      $filteredData = array_filter(UtilityHelper::getDocsTypes(), function ($item) use ($docs_types_in_required) {
        return isset($docs_types_in_required[$item['docs_type']]);
      });

      // Add is_required field to the filtered data
      $docs = array_map(function ($doc) use ($docs_types_in_required) {
        $doc['is_required'] = $docs_types_in_required[$doc['docs_type']];
        return $doc;
      }, array_values($filteredData));

      return $docs;
    } else {
      return [];
    }
  }

  public static function getAllDocsFields()
  {
    $config_keys = [
      'company_documents',
    ];

    $all_docs = [];

    foreach ($config_keys as $key) {
      $docs_types = Configsetting::where('key', $key)->first();
      $docs_required = isset($docs_types->value) ? json_decode($docs_types->value, true) : null;

      if ($docs_required) {
        // Create a map of docs_type to is_required
        $docs_types_in_required = [];
        foreach ($docs_required as $doc) {
          $docs_types_in_required[$doc['docs_type']] = $doc['is_required'];
        }

        // Filter the docs based on the docs_type in required
        $filteredData = array_filter(UtilityHelper::getDocsTypes(), function ($item) use ($docs_types_in_required) {
          return isset($docs_types_in_required[$item['docs_type']]);
        });

        // Add is_required field to the filtered data
        $all_docs = array_merge($all_docs, array_map(function ($doc) use ($docs_types_in_required) {
          $doc['is_required'] = $docs_types_in_required[$doc['docs_type']];
          return $doc;
        }, array_values($filteredData)));
      }
    }

    return $all_docs;
  }





  public static function loadDocumentsPath($documents, $folder)
  {
    if (!count($documents)) {
      return [];
    }
    // Log::info($folder . " -Documents :" . json_encode($documents));
    $docs = $documents;
    foreach ($docs as $doc) {
      $_docs_path = $doc->path; // isset($doc->path) ? json_decode($doc->path) : [];
      if (count($_docs_path)) {
        $_path = [];
        foreach ($_docs_path as $_doc_path) {
          $_path[] = $_doc_path; //S3Helper::getFileUrl($folder, $_doc_path);
        }
        $doc->path = $_path;
      }
    }
    return  $docs;
  }

  public static function loadDocumentPath($document, $folder)
  {
    if (!isset($document)) {
      return [];
    }
    $paths = [];
    $_docs_path = isset($document->path) ? json_decode($document->path) : [];

    if (is_array($_docs_path) && count($_docs_path)) {
      $_path = [];
      foreach ($_docs_path as $_doc_path) {
        $_path[] = S3Helper::getFileUrl($folder, $_doc_path);
      }
      $paths = $_path;
    }

    return  $paths;
  }

  public static function getConfigValue($key)
  {
    $value = Configsetting::where('key', $key)->first();
    return isset($value->value) ? $value->value : null;
  }
  public static function getConfig($key)
  {
    $value = Configsetting::where('key', $key)->first();
    return $value;
  }
  public static function get_company_code()
  {
    return 'NK-DOCS-2024';
  }
  public static function get_supplier_agreement_doc_code()
  {
    return 'NIK-SUP-AGR-2025';
  }
  public static function get_customer_agreement_doc_code()
  {
    return 'NIK-CUS-AGR-2025';
  }
  public  static function checkDocumentsExpiry($expiry_date, $expiryType = 'is_expired')
  {
    $is_near_expiry = false;
    $is_expired = false;

    $expiryDate = Carbon::parse($expiry_date);
    // Check if the document is already expired
    if ($expiryDate->isPast()) {
      $is_expired = true;
    }
    // Check if the document will expire within the next 2 days
    $twoDaysFromNow = Carbon::now()->addDays(2);
    if ($expiryDate->isFuture() && $expiryDate->lte($twoDaysFromNow)) {
      $is_near_expiry = true;
    }

    if ($expiryType == 'is_near_expiry') {
      return $is_near_expiry;
    } elseif ($expiryType == 'is_expired') {
      return $is_expired;
    }
    return [];
  }
  public static function getUserTypes()
  {
    $userTypes = [
      "employees" => "Employees",
      "suppliers" => "Suppliers",
      "drivers" => "Drivers",
      "customers" => "Customers",
    ];
    return $userTypes;
  }

  public static function getEmployeeDesignation()
  {
    $designation = ConfigHelper::getConfigValueInArray('designation_fields');
    // $designation = [
    //   'accounts' => __('common_lang.accounts'),
    //   'admin' => __('common_lang.admin'),
    //   'customer_manager' => __('common_lang.customer_manager'),
    //   'supplier_mamanger' => __('common_lang.supplier_mamanger'),
    //   'poc' => __('common_lang.poc'),
    //   'hr' => __('common_lang.hr'),
    //   'non' => __('common_lang.non'),
    // ];
    return $designation;
  }

  /**
   * Generate a unique API key.
   *
   * @param string $table The table to check for uniqueness.
   * @param string $column The column to check for uniqueness.
   * @param int $length The length of the generated API key.
   * @return string
   */
  public static function generateUniqueApiKey($length = 60)
  {
    $apiKey = Str::random($length);
    return $apiKey;
  }
  /**
   * Get Bank Account types.
   */
  public static function get_bank_accountypes()
  {
    $bank_accountypes = [
      'savings_account' => 'Savings account',
      'current_account' => 'Current account',
      'salary_account' => 'Salary account',
      'fixed_deposit_account' => 'Fixed deposit account',
      'recurring_deposit_account' => 'Recurring deposit account',
      'nri_accounts' => 'NRI accounts',
    ];
    return $bank_accountypes;
  }
  /**
   * Get fetch Not Uploaded Documents.
   */
  public static function fetchNotUploadedDocuments($type = "", $module = null)
  {
    if ($module != null) {
      $documents = UtilityHelper::getDocsFields($type);
      $pending_docs = [];
      $pending_docs = array_filter($documents, function ($item) use ($module) {
        foreach ($module->documents as $values) {
          if ($item['docs_type'] === $values->document_type) {
            return false; // Exclude this document if it matches
          }
        }
        return true; // Keep the document if no match is found
      });
      // Re-index the array to reset the keys
      return array_values($pending_docs);
    }
    return [];
  }

  /**
   * Get fetch Not Uploaded Documents.
   */
  public static function getRenewDocuments($config_key = '', $docs_type = '')
  {
    $documents = UtilityHelper::getDocsFields($config_key);
    $renew_docs = [];
    $renew_docs = array_filter($documents, function ($item) use ($docs_type) {
      if ($item['docs_type'] === $docs_type) {
        return true; // Exclude this document if it matches
      }
      return false; // Keep the document if no match is found
    });
    return  array_values($renew_docs);
  }


  public static function getDeviceType($userAgent)
  {
    if (preg_match('/mobile/i', $userAgent)) {
      return 'Mobile';
    } elseif (preg_match('/tablet/i', $userAgent)) {
      return 'Tablet';
    } else {
      return 'Desktop';
    }
  }

  public static function getBrowser($userAgent)
  {
    if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
      return 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
      return 'Firefox';
    } elseif (preg_match('/Chrome/i', $userAgent)) {
      return 'Chrome';
    } elseif (preg_match('/Safari/i', $userAgent)) {
      return 'Safari';
    } elseif (preg_match('/Opera/i', $userAgent)) {
      return 'Opera';
    } elseif (preg_match('/Netscape/i', $userAgent)) {
      return 'Netscape';
    } else {
      return 'Unknown';
    }
  }

  public static function getUserInfo($user_type = '')
  {
    $users = false;
    if (!$user_type) {
      $users = UsersModel::select('*')->get();
      return $users;
    } else {
      $users =  UsersModel::where('user_type', $user_type)->get();
      return $users;
    }
    return $users;
  }
  public static function currentDateTimeStandard($date = '')
  {

    // Check if the date is provided, otherwise use the current date and time
    if (empty($date)) {
      $date = date('Y-m-d H:i:s');
    }

    // Create a DateTime object from the provided date in 'Y-m-d H:i:s' format
    $dateFormat = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

    if ($dateFormat === false) {
      return [
        'success' => false,
        'message' => 'Error processing date',
      ];
    }

    // Set timezone to IST (Indian Standard Time)
    $dateFormat->setTimezone(new \DateTimeZone('Asia/Kolkata'));

    // Format the date to 'Y-m-d H:i:s' with IST timezone
    return $dateFormat->format('Y-m-d H:i:s');
  }
  /**
   * Get table details by dynamic parameters.
   */
  public static function getNameByCode($col_name = '', $value = '', $model = '')
  {
    // Build the fully qualified model name
    $modelName = "App\Models\\" . $model;

    // Validate the inputs and fetch the name directly
    if ($col_name && $value && class_exists($modelName)) {
      // Use the fully qualified class name for the query
      $record = $modelName::where($col_name, $value)->first();
      return $record; // or return specific attribute e.g. $record->fullname
    }
    return ''; // Return an empty string if conditions aren't met
  }
  public static function shortcutsNavigationModules()
  {
    $shotcuts_modules = [
      [
        'module' => 'products',
        'permission_id' => 'create.products',
        'title' => 'Create New Product',
        'icon' => 'bx bxs-credit-card-front',
        'url' => route('create.products'),
      ],
      [
        'module' => 'products',
        'permission_id' => 'create.products',
        'title' => 'Create New Product',
        'icon' => 'bx bxs-credit-card-front',
        'url' => route('create.products'),
      ],
      [
        'module' => 'products',
        'permission_id' => 'create.products',
        'title' => 'Create New Product',
        'icon' => 'bx bxs-credit-card-front',
        'url' => route('create.products'),
      ],
      [
        'module' => 'products',
        'permission_id' => 'create.products',
        'title' => 'Create New Product',
        'icon' => 'bx bxs-credit-card-front',
        'url' => route('create.products'),
      ],
    ];
    $createAccessModules = [];
    foreach ($shotcuts_modules as $key_s => $data) {
      if ($data['module'] && $data['permission_id']) {
        $isGrandAccess = UtilityHelper::CheckModulePermissions($data['module'], $data['permission_id']);
        if ($isGrandAccess) {
          $createAccessModules[] = $data;
        }
      }
    }
    return $createAccessModules;
  }
  // // Get product stages and status
  // public static function getProductStagesAndStatus()
  // {
  //   $stages = [
  //     'Bonding' => 'Bonding',
  //     'Tapedge' => 'Tapedge',
  //     'Zip Cover' => 'Zip Cover',
  //     'QC' => 'QC',
  //     'Packing' => 'Packing',
  //     'Ready for Shipment' => 'Ready for Shipment',
  //     'Shipped' => 'Shipped',
  //     'Returned' => 'Returned',
  //     'Cancelled' => 'Cancelled',
  //   ];
  //   $status = [
  //     'PENDING' => 'PENDING',
  //     'PASS' => 'PASS',
  //     'FAILED' => 'FAILED',
  //   ];
  //   return ['stages' => $stages, 'status' => $status];
  // }
// UtilityHelper.php
public static function getProductStagesAndStatus($currentStage = null, $currentStatus = null)
{
    $stages = [
        'Bonding',
        'Tapedge',
        'Zip Cover',
        'QC',
        'Packing',
        'Ready for Shipment',
        'Shipped',
        'Returned',
        'Cancelled',
    ];

    $status = [
        'PENDING',
        'PASS',
        'FAILED',
    ];

    // If no current stage is given, return all (setup/testing)
    if ($currentStage === null) {
        return [
            'stages' => array_combine($stages, $stages),
            'status' => array_combine($status, $status),
        ];
    }

    $allowedStages = [];
    $allowedStatus = $status; // by default, all statuses available

    switch ($currentStage) {
        case 'Bonding':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Tapedge', 'Zip Cover', 'Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding'];
            }else{
              $allowedStages = ['Bonding', 'Tapedge', 'Zip Cover','QC','Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];  
            }
            
            break;

        case 'Tapedge':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Zip Cover', 'Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding', 'Tapedge'];
            }
            break;

        case 'Zip Cover':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding', 'Tapedge'];
            }
            break;
              case 'QC':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Tapedge', 'Zip Cover', 'Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding'];
            }
            break;

        case 'Packing':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding', 'Tapedge', 'Zip Cover', 'Packing'];
            }
            break;

        case 'Ready for Shipment':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Shipped', 'Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding'];
            }
            break;

        case 'Shipped':
            if ($currentStatus === 'PASS') {
                $allowedStages = ['Returned', 'Cancelled'];
            } elseif ($currentStatus === 'FAILED') {
                $allowedStages = ['Bonding'];
            }
            break;

        case 'Returned':
        case 'Cancelled':
            // Terminal states, no further stages
            $allowedStages = [];
            $allowedStatus = [];
            break;

        default:
            $allowedStages = [];
            $allowedStatus = [];
            break;
    }

    return [
        'stages' => array_combine($allowedStages, $allowedStages),
        'status' => array_combine($allowedStatus, $allowedStatus),
    ];
}

// 1. if current stage is Bonding QC 
// -----------------------------
// if pass they can only go to next stage, like Tapedge,Zip Cover,Packing,Ready for Shipment,Shipped,Returned,Cancelled
// if fail they can only go to Bonding 


// 2. if current stage is Tapedge QC
// -----------------------------
// if pass they can only go to next stage, like Zip Cover,Packing,Ready for Shipment,Shipped,Returned,Cancelled
// if fail they can only go to Bonding and QC

// 3. if current stage is Zip Cover
// -----------------------------
// if pass they can only go to next stage, like Packing,Ready for Shipment,Shipped,Returned,Cancelled
// if fail they can only go to Bonding QC and Tapedge QC

// 4. if current stage is Packing
// -----------------------------
// if pass they can only go to next stage, like Ready for Shipment,Shipped,Returned,Cancelled
// if fail they can only go to Bonding QC,Tapedge QC,Zip Cover and Packing

// 5. if current stage is Ready for Shipment
// -----------------------------
// if pass they can only go to next stage, like Shipped,Returned,Cancelled
// if fail they can only go to Bonding QC

// 6. if current stage is Shipped
// -----------------------------
// if pass they can only go to next stage, like Returned,Cancelled
// if fail they can only go to Bonding QC

}