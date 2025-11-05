<?php

namespace App\Helpers;

use App\Models\user_management\Role;
use App\Models\user_management\UsersModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class LocaleHelper
{
    public static function getDataSheetImportFormat($file)
    {
        if (! $file instanceof \Illuminate\Http\UploadedFile) {
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
                    if ($key == 0) {
                        continue;
                    } // Skip the headers row
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
                'message' => 'Error processing file: '.$e->getMessage(),
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
                'date' => date_format($dateFormat, 'Y-m-d H:i:s'),
            ];
    }

    // Date range input date format --------------------------
    public static function dateRangeDateInputFormat($daterange = '')
    {
        $date = [];
        if ($daterange) {
            [$startDate, $endDate] = explode(' - ', $daterange);
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
        $str = [];
        $words = [
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
            90 => 'ninety',
        ];
        $digits = ['', 'hundred', 'thousand', 'lakh', 'crore'];

        // Process the integer part (Rupees)
        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number].' '.$digits[$counter].$plural.' '.$hundred :
                    $words[floor($number / 10) * 10].' '.$words[$number % 10].' '.$digits[$counter].$plural.' '.$hundred;
            } else {
                $str[] = null;
            }
        }

        // Process the Rupees part
        $Rupees = implode('', array_reverse($str));

        // Process the Paise part (decimal)
        if ($decimal) {
            $paise = ($decimal < 10) ? 'Zero '.$words[$decimal] :
                $words[floor($decimal / 10) * 10].' '.$words[$decimal % 10];
            $paise .= ' Paise';
        } else {
            $paise = '';
        }

        // Final formatting
        $currencyText = 'Rupees';

        return $currencyText.' '.($Rupees ? $Rupees.' ' : '').$paise.' Only';
    }

    /**
     * Format a given date with time.
     *
     * @param  string  $date  The date string to format.
     * @param  string  $format  The desired date-time format (default: 'd-m-Y H:i:s').
     * @return string|null Formatted date or null if input is invalid.
     */
    public static function formatDateWithTime($date, $format = 'd/m/Y H:i:s')
    {
        if (! $date) {
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
     * @param  string  $date  The date string to format.
     * @param  string  $format  The desired date-only format (default: 'd-m-Y').
     * @return string|null Formatted date or null if input is invalid.
     */
    public static function formatDateWithoutTime($date, $format = 'd-m-Y')
    {
        if (! $date) {
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
        if (! file_exists($path)) {
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

    public static function getProductSummaryCounts()
    {
        // Load stages & defect points from config
        $config = UtilityHelper::getProductStagesAndDefectPoints();
        $stages = collect($config['stages'] ?? []);
        $defectPoints = collect($config['defect_points'] ?? []);

        // 🟢 Total products (with location filter)
        $totalProductsQuery = DB::table('products');
        $totalProductsQuery = LocaleHelper::commonWhereLocationCheck($totalProductsQuery);
        $totalProducts = $totalProductsQuery->count();

        // 🟢 Total RFID tags (with location filter)
        $totalRfidQuery = DB::table('products')
            ->whereNotNull('rfid_tag');
        $totalRfidQuery = LocaleHelper::commonWhereLocationCheck($totalRfidQuery);
        $totalRfidTags = $totalRfidQuery->count();

        // 🟢 Total QA codes (with location filter)
        $totalQaQuery = DB::table('products')
            ->whereNotNull('qa_code');
        $totalQaQuery = LocaleHelper::commonWhereLocationCheck($totalQaQuery);
        $totalQaCode = $totalQaQuery->count();

        // 🟢 Subquery: latest QC + stage per product
        $latestHistory = DB::table('product_process_history as h')
            ->select('h.product_id', 'h.status', 'h.stages')
            ->join(DB::raw('(SELECT product_id, MAX(changed_at) as latest_change 
                        FROM product_process_history 
                        GROUP BY product_id) latest'), function ($join) {
                $join->on('h.product_id', '=', 'latest.product_id')
                    ->on('h.changed_at', '=', 'latest.latest_change');
            });

        // Apply location filter (for user)
        $latestHistory = LocaleHelper::commonWhereLocationCheck($latestHistory, 'h');

        // Wrap subquery
        $latest = DB::table(DB::raw("({$latestHistory->toSql()}) as t"))
            ->mergeBindings($latestHistory);

        // 🟢 QC status counts
        $totalPassed = (clone $latest)->where('t.status', 'PASS')->count();
        $totalFailed = (clone $latest)->where('t.status', 'FAIL')->count();
        $totalRework = (clone $latest)->where('t.status', 'REWORK')->count();
        $totalPending = (clone $latest)->where('t.status', 'PENDING')->count();

        // 🟢 Stage-wise counts (from config mapping)
        $stageCounts = [];
        foreach ($stages as $stage) {
            $stageCounts[$stage['value']] = (clone $latest)
                ->where('t.stages', $stage['value'])
                ->count();
        }

        // ✅ Return all results properly
        return [
            'total_products' => $totalProducts,
            'total_rfid_tags' => $totalRfidTags,
            'total_qa_code' => $totalQaCode,
            'total_passed' => $totalPassed,
            'total_failed' => $totalFailed,
            'total_rework' => $totalRework,
            'total_pending' => $totalPending,
            'stage_counts' => $stageCounts,
        ];
    }

    public static function getBondingProductSummaryCounts()
    {
        // Base query with location filter
        $baseQuery = DB::table('bonding_plan_products');
        $baseQuery = LocaleHelper::commonWhereLocationCheck($baseQuery);

        // Apply counts
        $totalModels = (clone $baseQuery)->count();
        $totalWritted = (clone $baseQuery)->where('is_write', 1)->count();
        $totalPending = (clone $baseQuery)->where('is_write', 0)->count();

        // Return structured data
        return [
            'total_model' => $totalModels,
            'total_qa_code' => $totalModels, // same as total_model for consistency
            'total_writted' => $totalWritted,
            'total_pending' => $totalPending,
        ];
    }

    public static function getStageName($value = '')
    {
        $stageName = '';
        $configData = UtilityHelper::getProductStagesAndDefectPoints();
        $stages = $configData['stages'] ?? [];
        if ($stages) {
            foreach ($stages as $stage) {
                if (! empty($stage['value']) && $stage['value'] === $value) {
                    $stageName = $stage['name']; // return display name
                    break;
                }
            }
        }

        return $stageName;
    }

    /**
     * Apply location-based filter to any query
     * Works for both aliased and non-aliased tables.
     *
     * Example:
     *   LocaleHelper::commonWhereLocationCheck($query);          // without alias
     *   LocaleHelper::commonWhereLocationCheck($query, 'p');     // with alias
     */
    public static function commonWhereLocationCheck($query, $tableAlias = null)
    {
        $user = Auth::user();
        $role_info = Role::find($user->role_id);
        // If not logged in, skip filtering
        if (! $user) {
            return $query;
        }

        // Super admin should see all data
        if (! empty($user->is_super_admin) || $user->is_super_admin) {
            return $query;
        }

        if ($role_info->role_type == 'admin_role') {
            return $query;
        }

        // Get user location(s)
        $locationId = $user->location_id ?? null;

        // If user has no location, skip filtering
        if (is_null($locationId)) {
            return $query;
        }

        // Determine the column name
        $column = $tableAlias ? "{$tableAlias}.location_id" : 'location_id';

        // Apply location filter
        if (is_array($locationId)) {
            $query->whereIn($column, $locationId);
        } else {
            $query->where($column, $locationId);
        }

        return $query;
    }

    /**
     * Get login User Location Id
     */
    public static function getLoginUserLocationId($user_id = null)
    {
        $user = Auth::user();

        // Get user location(s)
        return $user->location_id ?? 0;
    }

    /**
     * Get User Location Id
     */
    public static function getUserLocationId($user_id = null)
    {
        $user = UsersModel::find($user_id);

        // Get user location(s)
        return $user->location_id ?? 0;
    }
}
