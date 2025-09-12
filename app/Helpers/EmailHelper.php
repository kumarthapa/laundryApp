<?php

namespace App\Helpers;

use App\Models\settings\Configsetting;
use App\Http\Controllers\emails\EmailController;
use Illuminate\Support\Facades\Log;

class EmailHelper
{
    public static function sendRegistrationEmail($data = [])
    {
        try {
            $type = $data['type'] ?? '';
            switch ($type) {
                case 'employee_registration':
                    $emails = [
                        $data['user_email'] ?? '',
                        'sleep@company.com'
                    ];
                    $data['title'] = 'Employee Registration';
                    $data['email'] = $emails;
                    $data['subject'] = 'Registration success for ' . $data['name'] ?? '';
                    $data['message'] = 'We are excited to inform you that you have successfully registered as an employee.';
                    break;
                case 'user_registration':
                    $emails = [
                        $data['user_email'] ?? '',
                        'sleep@company.com'
                    ];
                    $data['title'] = 'User Registration';
                    $data['email'] = $emails;
                    $data['subject'] = 'Registration success for ' . $data['name'] ?? '';
                    $data['message'] = 'We are excited to inform you that you have successfully registered as,  ' . $data['role_name'] . ' User.';
                    break;
                default:
                    break;
            }
            $emailController = new EmailController();
            if ($emailController->sendCommonEmail($data)) {
                Log::info('Email Sent: ', $data);
            } else {
                Log::info('Failed to send email (sendCommonEmail) : ', $data);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send email : Error" . $e->getMessage() . "\n File: " . $e->getFile() . "\n Line: " . $e->getLine());
        }
    }
    public static function sendTourAndTripMail($data = [])
    {
        try {
            $type = $data['type'] ?? '';
            switch ($type) {
                case 'placing_vehicle':
                    $data['title'] = 'Vehicle Placing';
                    $data['email'] = $data['email'] ?? ['sleep@company.com'];
                    $data['subject'] = 'Vehicle Placed ' . $data['vehicle_number'] ?? '';
                    $data['message'] = 'We are excited to inform you that you have successfully Placed Vehicle,  ' . $data['vehicle_number'];
                    break;
                case 'supplier_vehicle_detail':
                    $data['title'] = 'Vehicle Placing';
                    $data['email'] = $data['email'] ?? ['sleep@company.com'];
                    $data['subject'] = 'Vehicle Placed ' . $data['vehicle_number'] ?? '';
                    $data['message'] = $data['supplier_name'] . ' Your vehicle has been Placed for new trip ' . $data['trip_id'];
                    break;
                default:
                    $data = [];
                    break;
            }
            if ($data && count($data) > 0) {
                $emailController = new EmailController();
                if ($emailController->sendCommonEmail($data)) {
                    Log::info('Email Sent: ', $data);
                } else {
                    Log::info('Failed to send email (sendCommonEmail) : ', $data);
                }
            } else {
                Log::info('Required data is missing : ', $data);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send email : Error" . $e->getMessage() . "\n File: " . $e->getFile() . "\n Line: " . $e->getLine());
        }
    }
}
