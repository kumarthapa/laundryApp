<?php

namespace App\Http\Controllers\settings;

use App\Helpers\StorageHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\settings\Configsetting;
use App\Models\user_management\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    protected $roles;

    public function __construct()
    {
        $this->roles = new Role;
    }

    public function index(Request $request)
    {
        // ---------------- Product Process Stages ----------------
        $product_process_stages = [];
        $config = UtilityHelper::getConfig('product_process_stages');
        if (! empty($config->value)) {
            $product_process_stages = json_decode($config->value, true);
        }
        // ---------------- Product Process Stages ----------------
        $product_defect_points = [];
        $config1 = UtilityHelper::getConfig('product_defect_points');
        if (! empty($config1->value)) {
            $product_defect_points = json_decode($config1->value, true);
        }

        // ---------------- Product Status ----------------
        $product_status = [];
        $config_status = UtilityHelper::getConfig('product_status');
        if (! empty($config_status->value)) {
            $product_status = json_decode($config_status->value, true);
        }
        // ---------------- Pass to view ----------------
        // $productConfig = UtilityHelper::getProductStagesAndDefectPoints();
        // print_r($productConfig);
        // exit;
        $data = [];
        $data['product_process_stages'] = $product_process_stages;
        $data['product_defect_points'] = $product_defect_points;
        $data['product_status'] = $product_status;
        $data['UtilityHelper'] = new UtilityHelper;

        return view('content.settings.list', $data);
    }

    public function save(Request $request)
    {
        $post_data = $request->all();
        $submit_form_name = $request->post('submit_form_name');

        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $config_data = [];
        switch ($submit_form_name) {
            case 'save_company_details':
                $result = $this->save_company_details($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;

            case 'save_designation_details':
                $result = $this->save_designation_details($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;

            case 'save_product_process_stages':
                $result = $this->save_product_process_stages($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;

            case 'save_product_defect_points':
                $result = $this->save_product_defect_points($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;

            case 'save_product_status':
                $result = $this->save_product_status($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;

            case 'save_api_integration_details':
                $result = $this->save_api_integration_details($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;
            case 'save_email_configuration':
                $result = $this->save_email_configuration($request);
                if (! $result['success']) {
                    return response()->json(['success' => false, 'message' => $result['error_msg']]);
                }
                $config_data = $result['form_data'];
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Settings Saved Failed! Something Went Wrong.']);
                break;
        }
        // print_r($config_data);
        // exit;
        Log::info('config_data :'.json_encode($config_data));
        DB::beginTransaction();
        try {
            $success = false;
            if ($config_data) {
                // Insert or update each config setting
                // Using updateOrInsert to avoid duplicates based on 'key' and 'name'
                Log::info('config_data :'.json_encode($config_data));

                foreach ($config_data as $data) {
                    Configsetting::updateOrInsert(
                        ['key' => $data['key'], 'name' => $data['name']], // where clause for update
                        $data // data to insert or update
                    );
                }
                $success = true;
            }
            if (! $success) {
                return response()->json(['success' => false, 'message' => 'Settings Saved Failed!']);
            }

            // Insert user activity --------------------- START ---------------------
            $settingData = [];
            foreach ($config_data as $val_data) {
                $settingData[] = [
                    'key' => $val_data['key'] ?? '',
                    'name' => $val_data['name'] ?? '',
                ];
            }
            $this->UserActivityLog(
                $request,
                [
                    'module' => 'settings',
                    'activity_type' => 'update',
                    'message' => 'Update settings: '.implode(',', array_column($settingData, 'key')),
                    'application' => 'web',
                    'data' => $settingData,
                ]
            );
            // Insert user activity --------------------- END ----------------------

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Settings Saved Successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'bg_color' => 'bg-danger',
            ]);
        }
    }

    /* ------------------  SAVE company details ----------------------- */
    public function save_company_details($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = $request->all();
        $form_data = [];
        $response = [];

        // Remove unnecessary fields
        unset($post_data['_token'], $post_data['submit_form_name']);

        // Upload company logos if provided
        if (! empty($post_data['logo_upload']) && is_array($post_data['logo_upload'])) {
            foreach ($post_data['logo_upload'] as $key_name => $file_data) {
                if (in_array($key_name, ['company_logo', 'company_brand_logo'])) {
                    try {
                        $result = StorageHelper::storeLogoFile($file_data, $key_name);
                        if ($result['success']) {
                            $post_data[$key_name] = $result['path'];
                        } else {
                            unset($post_data[$key_name]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to store {$key_name}: ".$e->getMessage());
                        unset($post_data[$key_name]);
                    }
                }
            }
        }
        unset($post_data['logo_upload']);

        // Prepare form data for saving
        foreach ($post_data as $key => $value) {
            // Skip null or empty string values (allows 0 or '0')
            if ($value !== null && $value !== '') {
                $form_data[] = [
                    'key' => $key,
                    'name' => __('settings.'.$key),
                    'value' => $value,
                    'user_id' => $user_id,
                ];
            }
        }

        $response['form_data'] = $form_data;
        $response['success'] = true;

        return $response;
    }

    /* ------------------  SAVE ratecard_fields ----------------------- */
    public function save_ratecard_fields($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = [];
        $post_data = $request->all();
        $response = [];
        $form_data = [];
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        if (isset($post_data['ratecard_fields']) && $post_data['ratecard_fields']) {
            $value = [];
            // Iterate through the new data
            foreach ($post_data['ratecard_fields'] as $_key => $_value) {
                $value[] = [
                    'key' => $_key,
                    'name' => $_value,
                ];
            }
            // Prepare data for update or insert
            $form_data[] = [
                'key' => $post_data['setting_key'],
                'name' => $post_data['setting_key_name'],
                'value' => json_encode($value),
                'user_id' => $user_id,
            ];
            $response['form_data'] = $form_data;
            $response['success'] = true;

            return $response;
        } else {
            // If the fields is empty
            $response['success'] = false;
            $response['error_msg'] = 'Save Failed! Please add some Fields.';

            return $response;
        }
    }

    /* ------------------  SAVE vehicle_details ----------------------- */
    public function save_vehicle_details($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = [];
        $post_data = $request->all();
        // print_r($post_data);
        // exit;
        $response = [];
        $form_data = [];
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        if (isset($post_data) && $post_data) {
            $setting_key = $post_data['setting_key'];
            $setting_key_name = $post_data['setting_key_name'];
            $vehicle_values = [];
            if (isset($post_data['vehicle_details_fields']) && $post_data['vehicle_details_fields']) {
                foreach ($post_data['vehicle_details_fields'] as $_key => $_value) {
                    // print_r($_value['name']);
                    // exit;
                    $vehicle_values[] = [
                        'name' => $_value['name'],
                        'value' => $_value['value'],
                    ];
                }
            }
            $form_data[] = [
                'key' => $setting_key,
                'name' => $setting_key_name,
                'value' => json_encode($vehicle_values),
                'user_id' => $user_id,
            ];
            $response['form_data'] = $form_data;
            $response['success'] = true;

            return $response;
        } else {
            // If the fields is empty
            $response['success'] = false;
            $response['error_msg'] = 'Save Failed! Something went wrong.';

            return $response;
        }
    }

    /* ------------------  SAVE documents ----------------------- */
    public function save_documents($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = $request->all();
        $response = [];
        $form_data = [];
        // Remove unnecessary fields
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        if (isset($post_data['documents'])) {
            $document = [];
            foreach ($post_data['documents'] as $docs) {
                // set default value
                if (! isset($docs['start_date'])) {
                    $docs['start_date'] = '0';
                }
                if (! isset($docs['has_expiry'])) {
                    $docs['has_expiry'] = '0';
                }
                $document[] = $docs;
            }
            // print_r($document);
            // exit;
            // Build the form_data array
            $form_data[] = [
                'key' => 'documents',
                'name' => 'Add Documents',
                'value' => json_encode($document), // Save as JSON string of the documents array
                'user_id' => $user_id,
            ];
            $response['form_data'] = $form_data;
            $response['success'] = 1;
        } else {
            // Handle failure case
            $response['success'] = 0;
            $response['error_msg'] = 'Save Failed! Something went wrong.';
        }

        // print_r($response);
        // exit;
        return $response;
    }

    /* ------------------  SAVE documents types ----------------------- */
    public function save_documents_types($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = $request->all();
        $response = [];
        $form_data = [];
        // print_r($post_data);
        // exit;
        // Remove unnecessary fields
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        $documents_info = [];
        if (isset($post_data['docs']) && $post_data['docs']) {
            foreach ($post_data['docs'] as $data) {
                $documents_info[] = [
                    'docs_type' => $data['docs_type'],
                    'is_required' => isset($data['is_required']) ? 1 : 0,
                ];
            }
        }
        $form_data[] = [
            'key' => $post_data['config_key'],
            'name' => $post_data['config_name'],
            'value' => json_encode($documents_info), // Save as JSON string of the documents array
            'user_id' => $user_id,
        ];
        if ($form_data) {
            $response['form_data'] = $form_data;
            $response['success'] = 1;
        } else {
            // Handle failure case
            $response['success'] = 0;
            $response['error_msg'] = 'Save Failed! Something went wrong.';
        }

        return $response;
    }

    public function save_product_defect_points($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = $request->all();
        $response = [];
        $form_data = [];

        unset($post_data['_token'], $post_data['submit_form_name']);

        if (! empty($post_data)) {
            $setting_key = $post_data['setting_key'];
            $setting_key_name = $post_data['setting_key_name'];
            $post_values = [];

            if (! empty($post_data['product_defect_points'])) {
                foreach ($post_data['product_defect_points'] as $stage_name => $stage_points) {
                    $stage_key = $stage_name;
                    foreach ($stage_points as $_value) {
                        // print_r($_value['name']);
                        // exit;
                        $post_values[$stage_key][] = [
                            'name' => $_value['name'],
                            'value' => $_value['value'],
                        ];
                    }
                }
            }

            $form_data[] = [
                'key' => $setting_key,
                'name' => $setting_key_name,
                'value' => json_encode($post_values),
                'user_id' => $user_id,
            ];
            // print_r($form_data);
            // exit;
            $response['form_data'] = $form_data;
            $response['success'] = true;

            return $response;
        } else {
            $response['success'] = false;
            $response['error_msg'] = 'Save Failed! Something went wrong.';

            return $response;
        }
    }

    public function save_product_process_stages($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = [];
        $post_data = $request->all();
        $response = [];
        $form_data = [];
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        if (isset($post_data) && $post_data) {
            $setting_key = $post_data['setting_key'];
            $setting_key_name = $post_data['setting_key_name'];
            $post_values = [];

            if (isset($post_data['product_process_stages']) && $post_data['product_process_stages']) {
                foreach ($post_data['product_process_stages'] as $_key => $_value) {
                    $post_values[] = [
                        'name' => $_value['name'],
                        'value' => $_value['value'],
                    ];
                }
            }
            $form_data[] = [
                'key' => $setting_key,
                'name' => $setting_key_name,
                'value' => json_encode($post_values),
                'user_id' => $user_id,
            ];
            // print_r($form_data);
            // exit;
            $response['form_data'] = $form_data;
            $response['success'] = true;

            return $response;
        } else {
            // If the fields is empty
            $response['success'] = false;
            $response['error_msg'] = 'Save Failed! Something went wrong.';

            return $response;
        }
    }

    public function save_product_status($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = $request->all();
        $response = [];
        $form_data = [];

        unset($post_data['_token']);
        unset($post_data['submit_form_name']);

        if (! empty($post_data)) {
            $setting_key = $post_data['setting_key']; // expected: 'product_status'
            $setting_key_name = $post_data['setting_key_name']; // expected: 'Product Status'
            $post_values = [];

            if (! empty($post_data['product_status'])) {
                foreach ($post_data['product_status'] as $_key => $_value) {
                    $post_values[] = [
                        'name' => $_value['name'],
                        'value' => $_value['value'],
                    ];
                }
            }

            $form_data[] = [
                'key' => $setting_key,
                'name' => $setting_key_name,
                'value' => json_encode($post_values),
                'user_id' => $user_id,
            ];

            $response['form_data'] = $form_data;
            $response['success'] = true;

            return $response;
        } else {
            $response['success'] = false;
            $response['error_msg'] = 'Save Failed! Something went wrong.';

            return $response;
        }
    }

    public function save_designation_details($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = [];
        $post_data = $request->all();
        $response = [];
        $form_data = [];
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        if (isset($post_data) && $post_data) {
            $setting_key = $post_data['setting_key'];
            $setting_key_name = $post_data['setting_key_name'];
            $vehicle_values = [];
            if (isset($post_data['designation_details_fields']) && $post_data['designation_details_fields']) {
                foreach ($post_data['designation_details_fields'] as $_key => $_value) {
                    $vehicle_values[] = [
                        'name' => $_value['name'],
                        'value' => $_value['value'],
                    ];
                }
            }
            $form_data[] = [
                'key' => $setting_key,
                'name' => $setting_key_name,
                'value' => json_encode($vehicle_values),
                'user_id' => $user_id,
            ];
            $response['form_data'] = $form_data;
            $response['success'] = true;

            return $response;
        } else {
            // If the fields is empty
            $response['success'] = false;
            $response['error_msg'] = 'Save Failed! Something went wrong.';

            return $response;
        }
    }

    /* ------------------  SAVE API integration details ----------------------- */
    public function save_api_integration_details($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = [];
        $post_data = $request->all();

        $form_data = [];
        $response = [];
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        foreach ($post_data['setting_key'] as $_key => $_value) {
            $form_data[] = [
                'key' => $_value,
                'name' => isset($post_data['setting_key_name'][$_key]) ? $post_data['setting_key_name'][$_key] : '',
                'value' => isset($post_data['value'][$_key]) ? $post_data['value'][$_key] : '',
                'user_id' => $user_id,
            ];
        }
        // print_r($form_data);
        // exit;
        $response['form_data'] = $form_data;
        $response['success'] = true;

        return $response;
    }

    /* ------------------  SAVE Email Configuration ----------------------- */
    public function save_email_configuration($request)
    {
        $user_id = UtilityHelper::getLoginUserInfo()->id;
        $post_data = [];
        $post_data = $request->all();
        // print_r($post_data);
        // exit;
        $form_data = [];
        $response = [];
        unset($post_data['_token']);
        unset($post_data['submit_form_name']);
        foreach ($post_data['setting_key'] as $_key => $_value) {
            $form_data[] = [
                'key' => $_value,
                'name' => isset($post_data['setting_key_name'][$_key]) ? $post_data['setting_key_name'][$_key] : '',
                'value' => isset($post_data['value'][$_key]) ? $post_data['value'][$_key] : '',
                'user_id' => $user_id,
            ];
        }
        $response['form_data'] = $form_data;
        $response['success'] = true;

        return $response;
    }
    // --------------------------------------- Common upload pending or new Documents -------------------------------------
    // --------------------------------------- Common upload pending or new Documents -------------------------------------

    /* ------------------  Get config values by config key ----------------------- */
    public function getconfigValuesByConfigkey(Request $request)
    {
        // print_r($request->all());
        // exit;
        $config_key = $request->get('key_name');
        try {
            $value_array = [];
            if ($config_key) {
                $value = Configsetting::where('key', $config_key)->first();
                if (isset($value->value)) {
                    $value_array = json_decode($value->value, true);

                    return response()->json(['success' => true, 'data' => $value, 'values' => $value_array]);
                }
            }

            return response()->json(['success' => false, 'message' => 'Undefine config Key', 'data' => []]);
        } catch (Exception $e) {
            // If an error occurs, roll back the transaction
            DB::rollBack();
            Log::error('Exception Error: '.$e->getMessage());
            $response['success'] = false;
            $response['message'] = 'Server Error!.';
            $response['error'] = 'Error:'.$e->getMessage().' , Line: '.$e->getLine().' , File: '.$e->getFile();

            return response()->json($response, 500);
        }
    }

    public function save_config(Request $request)
    {
        $post_data = [];
        $post_data = $request->all();
        // print_r($post_data);
        // exit;
        $response = [];
        $config_data = [];
        DB::beginTransaction();
        try {
            if (isset($post_data) && $post_data) {
                $setting_key = $post_data['setting_key'];
                if ($setting_key) {
                    $new_values = [];
                    if (isset($post_data['input_value']) && $post_data['input_value']) {
                        $new_values[] = [
                            'name' => $post_data['input_value']['name'],
                            'value' => $post_data['input_value']['value'],
                        ];
                        $response['config_data'] = $config_data;
                    }
                    $value = Configsetting::where('key', $setting_key)->first();
                    if (isset($value->value)) {
                        $old_values = json_decode($value->value, true);
                        $merge_values = array_merge($new_values, $old_values);
                    }
                }
                // print_r($merge_values);
                // exit;
                $config_data = [
                    'value' => json_encode($merge_values),
                ];
                $response['merge_values'] = $merge_values;
                $response['input_value'] = $post_data['input_value'];
            }
            $success = false;
            if ($config_data) {
                Configsetting::where('key', $setting_key)->update($config_data);
                $success = true;
            }
            if (! $success) {
                return response()->json(['success' => false, 'message' => 'Failed to add option!', 'response' => $response]);
            }
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Option Saved Successfully', 'response' => $response]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'bg_color' => 'bg-danger',
            ]);
        }
    }
}
