<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\DB;

class LocaleHelper
{

    public static function get_documents_field_types($menu_name = '', $menu_type = 'employees')
    {
        $documents_field_types = [];
        $documents_field_types = [
            'text' => 'TextField',
            'textarea' => 'TextArea',
            'select_option' => 'Dropdown Options',
            'checkbox' => 'Checkbox',
            'date' => 'Date'
        ];

        return $documents_field_types;
    }

    public static function getModuleNames()
    {
        return [
            'suppliers' => 'Supplierr',
            'employees' => 'Employees',
            'customers' => 'Customers',
            'vehicles' => 'Vehicles'
        ];
    }


    public static function getDataSheetImportFormat($file)
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            return [
                'success' => false,
                'message' => 'Invalid file provided.',
            ];
        }
        try {
            // Load the file into a collection
            $data = Excel::toCollection(null, $file->getRealPath());
            $formattedData = [];
            if ($data->isNotEmpty()) {
                $headers = $data[0][0]; // Extract headers
                // Loop through each row, skipping the header
                foreach ($data[0] as $key => $row) {
                    if ($key == 0) continue; // Skip the headers row
                    $formattedRow = [];
                    foreach ($headers as $index => $header) {
                        $formattedRow[$header] = $row[$index];
                    }
                    $formattedData[] = $formattedRow;
                }
            }
            return $formattedData;
        } catch (\Exception $e) {
            // Handle errors, log them if necessary
            return [
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage(),
            ];
        }
    }


    public static function dataSheetDateFormat($date = '')
    {
        // Check if the date format is 'DD/MM/YY' or 'DD/MM/YYYY'
        if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $date)) {
            // Format: DD/MM/YY
            $dateFormat = \DateTime::createFromFormat('d/m/y', $date);
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            // Format: DD/MM/YYYY
            $dateFormat = \DateTime::createFromFormat('d/m/Y', $date);
        } else {
            // Handle invalid date format
            return [
                'success' => false,
                'message' => 'Invalid date format',
            ];
        }

        // Check if the DateTime object was created successfully
        if ($dateFormat === false) {
            return [
                'success' => false,
                'message' => 'Error processing date',
            ];
        }

        // Set timezone to IST (Indian Standard Time)
        $dateFormat->setTimezone(new \DateTimeZone('Asia/Kolkata'));

        // Format the date to 'Y-m-d H:i:s' with IST timezone
        return
            [
                'success' => true,
                'message' => 'Invalid date format',
                'date' => date_format($dateFormat, 'Y-m-d H:i:s')
            ];
    }
    //Date range input date format --------------------------
    public static function dateRangeDateInputFormat($daterange = '')
    {
        $date = [];
        if ($daterange) {
            list($startDate, $endDate) = explode(' - ', $daterange);
            // Convert the dates to Y-m-d format
            $startDate = Carbon::createFromFormat('d/m/Y', $startDate)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::createFromFormat('d/m/Y', $endDate)->endOfDay()->format('Y-m-d H:i:s');
            $date = [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
            return $date;
        }
        return $date;
    }
    // get currency in words
    public static function amountInWords($number)
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        );
        $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');

        // Process the integer part (Rupees)
        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred :
                    $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else {
                $str[] = null;
            }
        }

        // Process the Rupees part
        $Rupees = implode('', array_reverse($str));

        // Process the Paise part (decimal)
        if ($decimal) {
            $paise = ($decimal < 10) ? "Zero " . $words[$decimal] :
                $words[floor($decimal / 10) * 10] . " " . $words[$decimal % 10];
            $paise .= ' Paise';
        } else {
            $paise = '';
        }

        // Final formatting
        $currencyText = "Rupees";
        return $currencyText . ' ' . ($Rupees ? $Rupees . ' ' : '') . $paise . ' Only';
    }


    /**
     * Format a given date with time.
     *
     * @param string $date       The date string to format.
     * @param string $format     The desired date-time format (default: 'd-m-Y H:i:s').
     * @return string|null       Formatted date or null if input is invalid.
     */
    public static function formatDateWithTime($date, $format = 'd/m/Y H:i:s')
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return null; // Return null for invalid dates
        }
    }

    /**
     * Format a given date without time.
     *
     * @param string $date       The date string to format.
     * @param string $format     The desired date-only format (default: 'd-m-Y').
     * @return string|null       Formatted date or null if input is invalid.
     */
    public static function formatDateWithoutTime($date, $format = 'd-m-Y')
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return null; // Return null for invalid dates
        }
    }


    protected static function getIndiaData()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $path = public_path('json/states-cities.json');
        if (!file_exists($path)) {
            return [];
        }
        $cache = json_decode(file_get_contents($path), true);
        return $cache ?? [];
    }

    // public static function getIndianStates()
    // {
    //     return collect(self::getIndiaData())->pluck('state_code')->all(); // Just get all state names
    // }

    public static function getCitiesByStateIndia($stateName)
    {
        $state = collect(self::getIndiaData())->firstWhere('state_code', $stateName);
        return $state['cities'] ?? [];
    }

    public static function getMasterMISreportHeader()
    {
        $table_headers = [
            'Trip Date',
            'Client',
            'Vertical',
            'Service Type',
            'City',
            'Year',
            'Month (P)',
            'Trip Type (Scheduled/Peak Trip/Adhoc)',
            'Rate Card Type',
            'Type of Operation',
            'Vehicle Number',
            'Vehicle Size',
            'Hours',
            'Working Days (Q)',
            'Estimated KMs',
            'Actual Distance (KM) [R]',
            'Overtime (Hours) (S)',
            'Toll (INR) (T)',
            'Parking (INR) (U)',
            'Fixed Cost (RC) (X)',
            'Fixed KM (RC)',
            'Variable Cost/KM (RC) (Y)',
            'Overtime Charge/Hour (RC) (Z)',
            'Fixed Cost as per Working (INR)',
            'Total Variable Cost (INR)',
            'Total Extra KM Cost (INR) (C)',
            'Total Overtime Cost (INR) (D)',
            'Toll & Parking Cost (INR) (E)',
            'Total Amount (A+B+C+D+E)',
            'SC OPS Approval',
            'Finance Comments',
            'Tour Id',
            'Trip Id',
            'Trip Status',
            'Vendor Advance'
        ];
        return $table_headers;
    }

    public static function getProductSummaryCounts()
    {
        $totalProducts = DB::table('products')->count();
        $totalRfidTags = DB::table('products')->whereNotNull('rfid_tag')->count();
        $totalPass = DB::table('products')->where('qc_status', 'PASS')->count();
        $totalFailed = DB::table('products')->where('qc_status', 'FAILED')->count();

        return [
            'total_products' => $totalProducts,
            'total_rfid_tags' => $totalRfidTags,
            'total_pass' => $totalPass,
            'total_failed' => $totalFailed
        ];
    }
public static function getProductStageAndStatus()
{
    $data = [];

    // Hardcoded keys
    $status_key = 'product_status';
    $stages_key = 'product_process_stages';

    // Fetch and decode product status
    $status_config = UtilityHelper::getConfig($status_key);
    $data['product_status'] = ($status_config && !empty($status_config->value))
        ? json_decode($status_config->value, true)
        : [];

    // Fetch and decode product process stages
    $stages_config = UtilityHelper::getConfig($stages_key);
    $data['product_process_stages'] = ($stages_config && !empty($stages_config->value))
        ? json_decode($stages_config->value, true)
        : [];

    return $data;
}

}
