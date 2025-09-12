<?php

namespace App\Http\Controllers\emails;

use App\Jobs\GeneralSendEmailJob; // Import the job
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Send a common email.
     *
     * @param array $data
     * @return string
     */
    public function sendCommonEmail($data = [])
    {
        // print_r($data);
        // exit;
        // Validate required fields
        $success = false;
        $validator = Validator::make($data, [
            'email' => 'required|array', // Ensure emails is an array
            'email.*' => 'required|email', // Validate each email in the array
            'subject' => 'required|string',
            'type' => 'required|string', // Ensure type is provided
        ]);

        if ($validator->fails()) {
            Log::error('Failed to dispatch email job: Missing required fields.  - Receiver email is required!');
            $success = false;
            // return response()->json(['error' => 'Missing required fields - Receiver email is required!'], 400);
        }

        try {
            // Dispatch the job
            GeneralSendEmailJob::dispatch($data);
            Log::info('Email job dispatched successfully for: ' . implode(', ', $data['email']));
            $success = true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch email job: ' . $e->getMessage());
            $success = false;
        }

        return $success;
    }

    /**
     * Generate a PDF for registration.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePDF()
    {
        $data = [
            'name' => 'Galla',
            'code' => 'EMP-0034',
            'date' => now()->toDateString(),
        ];

        // Generate PDF from the 'emails.registration' view
        $pdf = Pdf::loadView('emails.registration', compact('data'));

        // Download the generated PDF
        return $pdf->download('employee_registration.pdf');
    }
}