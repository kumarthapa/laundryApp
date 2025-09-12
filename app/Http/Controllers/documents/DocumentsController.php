<?php

namespace App\Http\Controllers\documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\user_management\Role;
use App\Models\settings\Configsetting;
use App\Models\user_management\Permission;
use App\Models\user_management\GrantsPermission;
use App\Helpers\TableHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\APIsHelper;
use App\Helpers\UtilityHelper;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Documents;

class DocumentsController extends Controller
{
  protected $roles;
  public function __construct()
  {
    $this->roles = new Role;
  }
  // --------------------------------------- Common upload pending or new Documents -------------------------------------
  public function uploadDocs(Request $request)
  {
    $post_data = $request->all();
    $response['post_data'] = $post_data;
    $config_key = $post_data['config_key'];
    $code = $post_data['code'];
    $folder_name = $post_data['folder_name'];
    $old_docs_id = isset($post_data['docs_id']) ? $post_data['docs_id'] : 0;
    if (!count($request->file()) > 0) {
      return response()->json(['success' => false, 'message' => 'Upload Failed! Please select image']);
    }
    DB::beginTransaction();
    try {
      // Start document upload
      if (!$config_key || !$code) {
        return response()->json(['success' => false, 'message' => 'Document Upload Failed! Error-1']);
      }
      $setting_docs = UtilityHelper::getDocsFields($config_key);
      if (isset($setting_docs) && count($setting_docs)) {
        $docs = new Documents();
        $result = $docs->uploadAndSaveDocs($request, $post_data, $code, $folder_name, $setting_docs, $old_docs_id);
        // if (!$result) {
        //   return response()->json(['success' => false, 'message' => 'Document Upload Failed! Error-2']);
        // }
        // Updating old docs status it will not appear on view
        if (isset($old_docs_id) && $old_docs_id) {
          $_data = ['status' => 'inactive'];
          $model = Documents::find($old_docs_id);
          $model->update($_data);
        }
        $response['success'] = true;
        $response['message'] = 'Document upload Successfully';
        $response['saved'] = 1;
      } else {
        return response()->json(['success' => false, 'message' => 'Document Upload Failed!']);
      }

      DB::commit();
      return response()->json($response, 200);
    } catch (Exception $e) {
      // If an error occurs, roll back the transaction
      DB::rollBack();
      Log::error("Exception Error: " . $e->getMessage());
      $response['success'] = false;
      $response['message'] = "Server Error!.";
      $response['error'] =  "uploadDocs Error:" . $e->getMessage() . " , Line: " . $e->getLine() . " , File: " . $e->getFile();
      return response()->json($response, 500);
    }
  }
  // --------------------------------------- Common upload pending or new Documents -------------------------------------

  public function documentVerification(Request $request)
  {

    $enable_attester_api_integration = UtilityHelper::getConfigValue('enable_attester_api_integration');
    if (!$enable_attester_api_integration) {
      return response()->json([
        'success' => false,
        'message' => 'The verification API service is not enabled!',
      ]);
    }
    // print_r($request->all());
    // exit;
    $request->validate([
      'document_name' => 'required|string',
      // 'vehicle_number' => 'required|string',
    ]);

    $document_name = $request->input('document_name');
    switch ($document_name) {
      case 'vehicle_rc':
        return $this->verifyVehicleRc($request);
        break;
      case 'driving_license':
        return $this->verifyDrivingLicense($request);
        break;
      case 'adhaar_number':
        return $this->verifyAadhaarNumber($request);
        break;
      case 'pan_number':
        return $this->verifyPanNumber($request);
        break;
      case 'gst_number':
        return $this->verifyGSTNumber($request);
        break;
      case 'account_number':
        return $this->verifyAccountNumber($request);
        break;
      default:
        return response()->json([
          'success' => false,
          'message' => 'Invalid document name!',
          'error_code' => 400,
        ]);
    }
  }

  // Get Verify Vehicle RC number
  public function verifyVehicleRc($request)
  {
    $vehicleNo = $request->input('vehicle_number');
    $response = APIsHelper::verifyVehicleRc($vehicleNo);
    //$response = APIsHelper::verifyTESTDATA('rc');
    // Debugging check (Remove this once confirmed working)
    // print_r($response);
    // exit;
    Log::info('APIsHelper verifyVehicleRc : ' . json_encode($response));
    if (!$response || count($response) < 0) {
      return response()->json([
        'success' => false,
        'message' => 'Vehicle RC verification failed!',
        'error_code' => 400,
      ]);
    } else if (!isset($response['valid'])) {
      Log::error('Error Invalid vehicle: ' . json_encode($response));
      return response()->json([
        'success' => false,
        'message' => 'Vehicle RC verification failed!',
        'error_code' => 400,
      ]);
    }
    Log::info('Successfully Verified Vehicle: ' . json_encode($response));
    return response()->json([
      'success' => true,
      'message' => 'Successfully Verified!',
      'data' => array_merge(
        ['vehicle_no' => $vehicleNo], // Add requested vehicle number
        $response                       // Include entire API response data
      ),
    ]);
  }
  // Verify Driving License API Endpoint
  public function verifyDrivingLicense($request)
  {
    $dl_number = $request->input('driving_license');
    $date_of_birth = $request->input('date_of_birth');

    if (!isset($dl_number)) {
      return response()->json([
        'success' => false,
        'message' => 'Verification failed! Please Enter a Valid DL Number.',
      ]);
    }

    if (!isset($date_of_birth)) {
      return response()->json([
        'success' => false,
        'message' => 'Verification failed! Please Enter a Valid Date of Birth.',
      ]);
    }

    //$response = APIsHelper::verifyTESTDATA('dl');
    $response = APIsHelper::verifyDlnumber($dl_number, $date_of_birth);
    Log::info('APIsHelper verifyDlnumber : ' . json_encode($response));
    if (isset($response['output']['valid']) && !$response['output']['valid']) {
      return response()->json([
        'success' => false,
        'message' => isset($response['output']['message']) ? $response['output']['message'] : 'DL verification failed! DL Invalid Number.',
      ]);
    } else if (isset($response['error'])) {
      return response()->json([
        'success' => false,
        'message' => isset($response['error']) ? $response['error'] : 'DL verification failed!',
      ]);
    }
    // print_r($response);
    // exit;
    return response()->json([
      'success' => true,
      'message' => 'Successfully Verified!',
      'data' => array_merge(
        ['dl_number' => $dl_number],
        ['dob' => $date_of_birth],
        $response['output'] ?? []
      ),
    ]);
  }
  // Get Verify Aadhaar number
  public function verifyAadhaarNumber($request)
  {
    $adhaar_number = $request->input('adhaar_number');
    $document_name = $request->input('document_name');

    //-------------- Aadhaar verification is not working ---------------
    return response()->json([
      'success' => false,
      'message' => 'Aadhaar verification is currently unavailable. Please enter a valid number manually to proceed!',
    ]);
    //-------------- Aadhaar verification is not working ---------------

    //$response = APIsHelper::verifyTESTDATA('adhaar');
    $response = APIsHelper::verifyAdhaarNumber($adhaar_number);
    Log::info('APIsHelper verifyAdhaarNumber : ' . json_encode($response));
    if (!$response || count($response) < 0) {
      return response()->json([
        'success' => false,
        'message' => 'Aadhaar verification failed!',
        'error_code' => 400,
      ]);
    } else if (!isset($response['valid']) || !$response['valid']) {
      Log::error('Error Invalid Number: ' . json_encode($response));
      return response()->json([
        'success' => false,
        'message' => 'Aadhaar verification failed!! Invalid Number.',
        'error_code' => 400,
      ]);
    }
    Log::info('Successfully Verified Aadhaar Number: ' . json_encode($response));
    return response()->json([
      'success' => true,
      'message' => 'Successfully Verified Aadhaar!! <b>' . $adhaar_number . '</b>',
      'data' => array_merge(
        ['adhaar_number' => $adhaar_number, 'document_name' => $document_name], // Add requested adhaar_number
        $response                       // Include entire API response data
      ),
    ]);
  }
  // Get Verify Aadhaar number
  public function verifyPanNumber($request)
  {
    $pan_number = $request->input('pan_number');
    $document_name = $request->input('document_name');
    //$response = APIsHelper::verifyTESTDATA('pan');
    $response = APIsHelper::verifyPanNumber($pan_number);
    // Debugging check (Remove this once confirmed working)
    Log::info('APIsHelper verifyPanNumber : ' . json_encode($response));
    if (!$response || count($response) < 0) {
      return response()->json([
        'success' => false,
        'message' => 'PAN verification failed!',
        'error_code' => 400,
      ]);
    } else if (!isset($response['valid']) || !$response['valid']) {
      Log::error('Error Invalid Number: ' . json_encode($response));
      return response()->json([
        'success' => false,
        'message' => 'PAN verification failed!! Invalid Number.',
        'error_code' => 400,
      ]);
    }
    Log::info('Successfully Verified PAN Number: ' . json_encode($response));
    return response()->json([
      'success' => true,
      'message' => 'Successfully Verified PAN!! <b>' . $pan_number . '</b>',
      'data' => array_merge(
        ['pan_number' => $pan_number, 'document_name' => $document_name], // Add requested PAN number
        $response                       // Include entire API response data
      ),
    ]);
  }
  // ----------------- Get Verify GST number ----------------------------
  public function verifyGSTNumber($request)
  {
    $gst_number = $request->input('gst_number');
    $document_name = $request->input('document_name');
    //$response = APIsHelper::verifyTESTDATA('gst');
    $response = APIsHelper::verifyGSTNumber($gst_number);
    // Debugging check (Remove this once confirmed working)
    Log::info('APIsHelper verifyGSTNumber : ' . json_encode($response));
    if (!$response || count($response) < 0) {
      return response()->json([
        'success' => false,
        'message' => 'GST verification failed!',
        'error_code' => 400,
      ]);
    } else if (!isset($response['valid']) || !$response['valid']) {
      Log::error('Error Invalid Number: ' . json_encode($response));
      return response()->json([
        'success' => false,
        'message' => 'GST verification failed!! Invalid Number.',
        'error_code' => 400,
      ]);
    }
    Log::info('Successfully Verified GST Number: ' . json_encode($response));
    return response()->json([
      'success' => true,
      'message' => 'Successfully Verified GST!! <b>' . $gst_number . '</b>',
      'data' => array_merge(
        ['gst_number' => $gst_number, 'document_name' => $document_name], // Add requested gst_number
        $response                       // Include entire API response data
      ),
    ]);
  }

  // ----------------- Get Verify Account Number ----------------------------
  public function verifyAccountNumber($request)
  {
    $account_number = $request->input('account_number');
    $account_number = $request->input('account_number');
    $ifsc_code = $request->input('ifsc_code');
    //$response = APIsHelper::verifyTESTDATA('account_number');
    $response = APIsHelper::verifyAccountNumber($account_number, $ifsc_code);
    // Debugging check (Remove this once confirmed working)
    Log::info('APIsHelper verifyAccountNumber : ' . json_encode($response));
    if (!$response || count($response) < 0) {
      return response()->json([
        'success' => false,
        'message' => 'Account verification failed!',
        'error_code' => 400,
      ]);
    } else if (!isset($response['valid']) || !$response['valid']) {
      Log::error('Error Invalid Number: ' . json_encode($response));
      return response()->json([
        'success' => false,
        'message' => 'Account verification failed!! Account Number.',
        'error_code' => 400,
      ]);
    }
    Log::info('Successfully Verified Account Number: ' . json_encode($response));
    return response()->json([
      'success' => true,
      'message' => 'Successfully Verified Account Number!! <br><b>A/C: ' . $account_number . '</b>',
      'data' => array_merge(
        ['account_number' => $account_number], // Add requested gst_number
        $response                       // Include entire API response data
      ),
    ]);
  }

  public function uploadDocumentOCRVerification($file_data = [])
  {
    $enable_attester_api_integration = UtilityHelper::getConfigValue('enable_attester_api_integration');
    if (!$enable_attester_api_integration) {
      return response()->json([
        'success' => false,
        'message' => 'The verification API service is not enabled!',
      ]);
    }

    //-------------- Bank Cheque Book OCR API is not working ( "media ID" isn't verifying) ---------------
    return response()->json([
      'success' => false,
      'message' => "Bank Cheque Book OCR API is not working (media ID isn't verifying)!",
    ]);
    //-------------- Bank Cheque Book OCR API is not working ---------------

    $media_data = APIsHelper::getMediaId($file_data);
    Log::info('APIsHelper getMediaId : ' . json_encode($media_data));
    if (isset($media_data['_id']) && isset($media_data['originalName'])) {
      $response = APIsHelper::verifyBankCheque($media_data);
      Log::info('APIsHelper verifyBankCheque : ' . json_encode($response));
      // print_r($response);
      // exit;
      // Log::info('APIsHelper verifyAccountNumber : ' . json_encode($response));
    } else {
      return response()->json([
        'success' => false,
        'message' => 'Verification failed!',
        'error_code' => 400,
      ]);
    }
  }
}