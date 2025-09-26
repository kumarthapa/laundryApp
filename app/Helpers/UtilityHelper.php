<?php

namespace App\Helpers;

use App\Models\settings\Configsetting;
use App\Models\user_management\GrantsPermission;
// use CustomHelper;
use App\Models\user_management\Permission;
use App\Models\user_management\Role;
use App\Models\user_management\UsersModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
        if (! $role_id || ! $module_id) {
            return false;
        }

        // Fetch grants for this role and module
        $result = GrantsPermission::where([
            ['role_id', $role_id],
            ['module_id', $module_id],
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
        $result = false;
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
        // print_r($permissions_info); exit;
        foreach ($permissions_info as $per) {
            return $per->permission_name;
        }
        // return $module_permission;
    }

    public static function getUserRoleInfo($role_id = '')
    {
        $result = false;
        if (Auth::check() && ! $role_id) {
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
     * @param  int  $length  The length of the random string to generate.
     * @param  string|null  $prefix  The prefix to prepend to the generated string.
     * @param  bool  $has_numbers  Whether to include numbers in the generated string.
     * @param  bool  $has_capitals  Whether to include capital letters in the generated string.
     * @param  bool  $only_capitals  Whether to use only capital letters in the generated string.
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

    public static function getEmployeeDesignation()
    {
        $designation = ConfigHelper::getConfigValueInArray('designation_fields');

        return $designation;
    }

    /**
     * Generate a unique API key.
     *
     * @param  string  $table  The table to check for uniqueness.
     * @param  string  $column  The column to check for uniqueness.
     * @param  int  $length  The length of the generated API key.
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
        if (preg_match('/MSIE/i', $userAgent) && ! preg_match('/Opera/i', $userAgent)) {
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
        if (! $user_type) {
            $users = UsersModel::select('*')->get();

            return $users;
        } else {
            $users = UsersModel::where('user_type', $user_type)->get();

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
        $modelName = "App\Models\\".$model;

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

    public static function getProductStagesAndStatusBK($currentStage = null, $currentStatus = null)
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
                } else {
                    $allowedStages = ['Bonding', 'Tapedge', 'Zip Cover', 'QC', 'Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'];
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

    /**
     * Get product stages, defect points, and status from config
     *
     * @return array
     */
    public static function getProductStagesAndDefectPoints()
    {
        // ---------------- Product Process Stages ----------------
        $product_process_stages = [];
        $configStages = UtilityHelper::getConfig('product_process_stages');
        if (! empty($configStages->value)) {
            $product_process_stages = json_decode($configStages->value, true);
        }

        // ---------------- Product Defect Points ----------------
        $product_defect_points = [];
        $configDefects = UtilityHelper::getConfig('product_defect_points');
        if (! empty($configDefects->value)) {
            $product_defect_points = json_decode($configDefects->value, true);
        }

        // ---------------- Product Status ----------------
        $product_status = [];
        $configStatus = UtilityHelper::getConfig('product_status');
        if (! empty($configStatus->value)) {
            $product_status = json_decode($configStatus->value, true);
        }

        // ---------------- Return combined array ----------------
        return [
            'stages' => $product_process_stages,
            'defect_points' => $product_defect_points,
            'status' => $product_status,
        ];
    }

    public static function getProductStagesAndStatus($currentStage = null, $currentStatus = null)
    {
        // ---------------- Product Process Stages ----------------
        $product_process_stages = [];
        $configStages = UtilityHelper::getConfig('product_process_stages');
        if (! empty($configStages->value)) {
            $product_process_stages = json_decode($configStages->value, true);
        }

        // ---------------- Product Defect Points ----------------
        $product_defect_points = [];
        $configDefects = UtilityHelper::getConfig('product_defect_points');
        if (! empty($configDefects->value)) {
            $product_defect_points = json_decode($configDefects->value, true);
        }

        // ---------------- Product Status ----------------
        $product_status = [];
        $configStatus = UtilityHelper::getConfig('product_status');
        if (! empty($configStatus->value)) {
            $product_status = json_decode($configStatus->value, true);
        }

        // Build stage maps
        $stages_values = [];
        $stages_map = [];
        foreach ($product_process_stages as $s) {
            if (isset($s['value'])) {
                $stages_values[] = $s['value'];
                $stages_map[$s['value']] = $s['name'] ?? $s['value'];
            }
        }

        // Build status maps
        $status_map = [];
        $status_values = [];
        foreach ($product_status as $st) {
            if (isset($st['value'])) {
                $status_values[] = $st['value'];
                $status_map[$st['value']] = $st['name'] ?? $st['value'];
            }
        }

        // Normalize input stage
        $normalizedStageValue = null;
        if (! empty($currentStage)) {
            if (in_array($currentStage, $stages_values, true)) {
                $normalizedStageValue = $currentStage;
            } else {
                foreach ($product_process_stages as $s) {
                    if (! empty($s['name']) && strcasecmp($s['name'], $currentStage) === 0) {
                        $normalizedStageValue = $s['value'];
                        break;
                    }
                }
            }
        }

        // Normalize input status
        $normalizedStatusValue = null;
        if (! empty($currentStatus)) {
            if (in_array($currentStatus, $status_values, true)) {
                $normalizedStatusValue = $currentStatus;
            } else {
                foreach ($product_status as $st) {
                    if (! empty($st['name']) && strcasecmp($st['name'], $currentStatus) === 0) {
                        $normalizedStatusValue = $st['value'];
                        break;
                    }
                }
            }
        }

        // If no stage sent, return full config
        if ($normalizedStageValue === null) {
            return [
                'stages' => $stages_map,
                'defect_points' => $product_defect_points,
                'status' => $status_map,
            ];
        }

        // Determine allowed stages based on status
        $index = array_search($normalizedStageValue, $stages_values, true);
        $allowed_stage_values = [];

        if ($index === false) {
            // Unknown stage, return empty allowed stages but full status
            return [
                'stages' => [],
                'defect_points' => [],
                'status' => $status_map,
            ];
        }

        // Forward/backward logic based on status
        $pass_like = ['PASS', 'PASS'];
        $failed_like = ['FAIL', 'FAIL'];

        $statusLower = strtolower($normalizedStatusValue ?? '');
        if (in_array($statusLower, $pass_like, true)) {
            $allowed_stage_values = array_slice($stages_values, $index + 1);
        } elseif (in_array($statusLower, $failed_like, true)) {
            $allowed_stage_values = array_slice($stages_values, 0, $index + 1);
        } else {
            $allowed_stage_values = $stages_values; // unknown or empty => all
        }

        // Map allowed stages
        $allowed_stages_map = [];
        foreach ($allowed_stage_values as $val) {
            $allowed_stages_map[$val] = $stages_map[$val] ?? $val;
        }

        // Filter defect points for allowed stages
        $filtered_defect_points = [];
        foreach ($allowed_stage_values as $val) {
            $filtered_defect_points[$val] = $product_defect_points[$val] ?? [];
        }

        return [
            'stages' => $allowed_stages_map,
            'defect_points' => $filtered_defect_points,
            'status' => $status_map,
        ];
    }
}
