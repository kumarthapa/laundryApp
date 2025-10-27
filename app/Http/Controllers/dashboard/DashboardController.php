<?php

namespace App\Http\Controllers\dashboard;

use App\Exports\ProductExport;
use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\products\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Assuming this is where getProductStagesAndDefectPoints is defined
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $metrics = $this->gatherMetrics();
        $headers = [
            ['updated_date' => 'Updated Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['qa_code' => 'QA Code'],
            ['stage' => 'Stage'],
            ['status' => 'Status'],
            // ['defects_points' => 'Defect Points'],
            ['comments' => 'Comments'],
        ];

        $configData = UtilityHelper::getProductStagesAndDefectPoints();
        $table_headers = TableHelper::get_manage_table_headers($headers, false, true, false, false, false);

        return view('content.dashboard.dashboards-analytics', [
            'metrics' => $metrics,
            'LocaleHelper' => new LocaleHelper,
            'table_headers' => $table_headers,
            'stages' => $configData['stages'] ?? [],
            'defect_points' => $configData['defect_points'] ?? [],
            'status' => $configData['status'] ?? [],
        ]);
    }

    /**
     * Build a single row for datatable from DB row/stdClass
     */
    protected function tableHeaderRowData($row)
    {
        // print_r($row);
        // exit;
        $data = [];
        // Updated Date
        $data['updated_date'] = LocaleHelper::formatDateWithTime($row->updated_date ?? '');
        $data['product_name'] = $row->product_name;
        // SKU & QA Code
        $data['sku'] = e($row->sku);
        $data['qa_code'] = e($row->qa_code);

        // Stage
        $stageLabel = $this->stageMap[$row->stage] ?? $row->stage ?? '-';
        $data['stage'] = '<span class="badge rounded bg-label-secondary"><i class="icon-base bx bx-message-alt-detail me-1"></i>'.e($stageLabel).'</span>';

        // Status badge (PASS / FAIL / REWORK / PENDING)
        $status = strtoupper(trim($row->status ?? ''));
        $statusHTML = match ($status) {
            'PASS' => '<span class="badge rounded bg-label-success"><i class="icon-base bx bx-check-circle me-1"></i>PASS</span>',
            'FAIL' => '<span class="badge rounded bg-label-danger"><i class="icon-base bx bx-x-circle me-1"></i>FAIL</span>',
            'REWORK' => '<span class="badge rounded bg-label-warning"><i class="icon-base bx bx-refresh me-1"></i>REWORK</span>',
            default => '<span class="badge rounded bg-label-primary"><i class="icon-base bx bx-time me-1"></i>PENDING</span>',
        };
        $data['status'] = $statusHTML;

        // Defect Points (stored as JSON)
        // $defectsHtml = '';
        // $defectsRaw = $row->defects_points ?? null;

        // if (! empty($defectsRaw)) {
        //     $decoded = is_string($defectsRaw) ? @json_decode($defectsRaw, true) : (is_array($defectsRaw) ? $defectsRaw : []);
        //     if (is_array($decoded) && count($decoded) > 0) {
        //         $badges = [];
        //         foreach ($decoded as $d) {
        //             $label = $this->defectPointMap[$d] ?? $d;
        //             $badges[] = '<span class="badge rounded bg-label-info me-1">'.e($label).'</span>';
        //         }
        //         $defectsHtml = implode(' ', $badges);
        //     }
        // }
        // $data['defects_points'] = $defectsHtml ?: '-';

        // Comments
        $data['comments'] = e($row->comments ?? '');

        return $data;
    }

    public function list(Request $request)
    {
        $search = $request->get('search') ?? '';
        $limit = intval($request->get('length', 100));
        $offset = intval($request->get('start', 0));
        $sort = $request->get('sort') ?? 'h.changed_at';
        $order = $request->get('order') ?? 'desc';

        // Normalize filters (support old/new param names)
        $filters = [
            'status' => $request->get('qc_status') ?? $request->get('status') ?? '',
            'stages' => $request->get('current_stage') ?? $request->get('stages') ?? '',
            'defects_points' => $request->get('defect_points') ?? $request->get('defects_points') ?? '',
        ];

        // Normalize “ALL” → no filter
        foreach (['status', 'stages', 'defects_points'] as $k) {
            if (is_string($filters[$k]) && strtolower($filters[$k]) === 'all') {
                $filters[$k] = '';
            }
        }

        // Convert defect points (comma-separated → array)
        if (! empty($filters['defects_points']) && is_string($filters['defects_points']) && strpos($filters['defects_points'], ',') !== false) {
            $filters['defects_points'] = array_values(array_filter(array_map('trim', explode(',', $filters['defects_points']))));
        }

        // Date range filter
        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);

        $filters['start_date'] = $daterange['start_date'] ?? Carbon::now()->subMonth()->startOfDay();
        $filters['end_date'] = $daterange['end_date'] ?? Carbon::now()->endOfDay();

        // Main query (direct from history)
        $query = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->select(
                DB::raw("DATE_FORMAT(h.changed_at, '%Y-%m-%d %H:%i:%s') as updated_date"),
                'p.product_name',
                'p.sku',
                'p.qa_code',
                'h.stages as stage',
                'h.status as status',
                'h.defects_points',
                'h.remarks as comments'
            );
        // Apply location filter (for user)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'h');
        // 🔍 Search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.qa_code', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.remarks', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }

        // 🧩 Stage filter
        if (! empty($filters['stages'])) {
            $query->where('h.stages', $filters['stages']);
        }

        // ✅ Status filter
        if (! empty($filters['status'])) {
            $query->where('h.status', strtoupper($filters['status']));
        }

        // 💥 Defects Points (JSON filter)
        if (! empty($filters['defects_points'])) {
            foreach ((array) $filters['defects_points'] as $def) {
                $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [json_encode($def)]);
            }
        }

        // 📅 Date filter (based on updated date)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
        }

        // Sort & paginate
        $query->orderBy($sort, $order)
            ->limit($limit)
            ->offset($offset);

        $searchData = $query->get();

        // Count total (for pagination)
        $totalrowsQuery = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->when(! empty($search), function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('p.product_name', 'like', "%{$search}%")
                        ->orWhere('p.sku', 'like', "%{$search}%")
                        ->orWhere('p.qa_code', 'like', "%{$search}%")
                        ->orWhere('h.stages', 'like', "%{$search}%")
                        ->orWhere('h.status', 'like', "%{$search}%")
                        ->orWhere('h.remarks', 'like', "%{$search}%")
                        ->orWhere('h.defects_points', 'like', "%{$search}%");
                });
            });

        $totalrowsQuery = LocaleHelper::commonWhereLocationCheck($totalrowsQuery, 'h');
        $total_rows = $totalrowsQuery->count();

        // Prepare formatted rows
        $data_rows = [];
        foreach ($searchData as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }

        return response()->json([
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ]);
    }

    // JSON endpoint for polling / refreshing charts
    public function metrics(Request $request)
    {
        $metrics = $this->gatherMetrics();

        return response()->json($metrics);
    }

    protected function gatherMetrics()
    {
        // -----------------------------------------------------
        // 1) TOTAL PRODUCTS (with location filter)
        // -----------------------------------------------------
        $totalProductsQuery = DB::table('products');
        $totalProductsQuery = LocaleHelper::commonWhereLocationCheck($totalProductsQuery);
        $totalProducts = $totalProductsQuery->count();

        $stages = ['bonding_qc', 'tape_edge_qc', 'zip_cover_qc', 'packaging'];

        // -----------------------------------------------------
        // 2) DAILY QC COUNTS (Today’s PASS counts per stage)
        // -----------------------------------------------------
        $daily_countsQuery = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->select('h.stages', DB::raw('COUNT(*) as total'))
            ->whereDate('h.created_at', Carbon::today())
            ->where('h.status', 'PASS')
            ->whereIn('h.stages', $stages)
            ->groupBy('h.stages');

        $daily_countsQuery = LocaleHelper::commonWhereLocationCheck($daily_countsQuery, 'p');
        $daily_counts = $daily_countsQuery->pluck('total', 'stages')->toArray();

        $daily_bonding = $daily_counts['bonding_qc'] ?? 0;
        $daily_tape_edge_qc = $daily_counts['tape_edge_qc'] ?? 0;
        $daily_zip_cover_qc = $daily_counts['zip_cover_qc'] ?? 0;
        $daily_packaging = $daily_counts['packaging'] ?? 0;

        // -----------------------------------------------------
        // 3) TOTAL PACKAGING (Overall PASS count for packaging)
        // -----------------------------------------------------
        $total_packagingQuery = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->where('h.stages', 'packaging')
            ->where('h.status', 'PASS');
        $total_packagingQuery = LocaleHelper::commonWhereLocationCheck($total_packagingQuery, 'p');
        $total_packaging = $total_packagingQuery->count();

        // -----------------------------------------------------
        // 4) CONFIG ARRAYS for stages and defect points
        // -----------------------------------------------------
        $productConfig = UtilityHelper::getProductStagesAndDefectPoints();
        $stagesConfig = $productConfig['stages'] ?? [];

        // -----------------------------------------------------
        // 5) STAGE DISTRIBUTION (last 30 days actual counts)
        //    ✅ FIXED: previously only counted latest stage.
        //    Now counts all product-stage entries in last 30 days.
        // -----------------------------------------------------
        $startDate = Carbon::today()->subDays(29)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $stageCountsRaw = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->select('h.stages as stage', DB::raw('COUNT(DISTINCT h.product_id) as cnt'))
            ->whereBetween('h.changed_at', [$startDate, $endDate])
            ->where('h.status', 'PASS')
            ->groupBy('h.stages');

        $stageCountsRaw = LocaleHelper::commonWhereLocationCheck($stageCountsRaw, 'p')
            ->pluck('cnt', 'stage')
            ->toArray();

        $stageCounts = $stageCountsRaw;

        // -----------------------------------------------------
        // 6) QC STATUS COUNTS (PASS/FAIL/REWORK etc.)
        //    Based on latest history per product.
        // -----------------------------------------------------
        $latestHistorySubquery = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->select('h.product_id', DB::raw('MAX(h.id) as max_id'))
            ->where('h.status', 'PASS')
            ->groupBy('h.product_id');
        $latestHistorySubquery = LocaleHelper::commonWhereLocationCheck($latestHistorySubquery, 'p');

        $qcCountsRaw = DB::table('products as p')
            ->joinSub($latestHistorySubquery, 'latest_h', function ($join) {
                $join->on('p.id', '=', 'latest_h.product_id');
            })
            ->join('product_process_history as h', 'h.id', '=', 'latest_h.max_id')
            ->whereNotNull('h.status')
            ->select('h.status', DB::raw('COUNT(p.id) as cnt'))
            ->groupBy('h.status')
            ->pluck('cnt', 'status')
            ->toArray();

        $qcCounts = $qcCountsRaw;

        // -----------------------------------------------------
        // 7) DAILY THROUGHPUT (Last 30 days, stage-wise)
        // -----------------------------------------------------
        $rows = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->select('h.stages', DB::raw('DATE(h.changed_at) as day'), DB::raw('COUNT(*) as cnt'))
            ->where('h.status', 'PASS')
            ->whereBetween('h.changed_at', [$startDate, $endDate])
            ->groupBy('h.stages', DB::raw('DATE(h.changed_at)'))
            ->orderBy('h.stages')
            ->orderBy('day');

        $rows = LocaleHelper::commonWhereLocationCheck($rows, 'p')->get();

        // Generate date range for last 30 days
        $dates = [];
        for ($d = 0; $d < 30; $d++) {
            $dates[] = $startDate->copy()->addDays($d)->toDateString();
        }

        // Build stage-wise series data
        $stageSeries = [];
        foreach ($rows as $r) {
            $stage = $r->stages;
            $day = $r->day;
            $stageSeries[$stage][$day] = (int) $r->cnt;
        }

        // Fill missing dates with 0
        foreach ($stageSeries as $stage => $dayCounts) {
            foreach ($dates as $d) {
                if (! isset($stageSeries[$stage][$d])) {
                    $stageSeries[$stage][$d] = 0;
                }
            }
            ksort($stageSeries[$stage]);
        }

        // -----------------------------------------------------
        // 8) STUCK ITEMS (not progressed in last 7 days)
        // -----------------------------------------------------
        $daysStuck = 7;
        $stuckCutoff = Carbon::now()->subDays($daysStuck);
        $stuckItems = DB::table('products as p')
            ->joinSub($latestHistorySubquery, 'latest_h', function ($join) {
                $join->on('p.id', '=', 'latest_h.product_id');
            })
            ->join('product_process_history as h', 'h.id', '=', 'latest_h.max_id')
            ->where('p.updated_at', '<', $stuckCutoff)
            ->whereNotIn('h.stages', ['packaging'])
            ->orderBy('p.updated_at', 'asc')
            ->limit(20)
            ->get([
                'p.id',
                'p.sku',
                'p.product_name',
                'h.stages as current_stage',
                'p.updated_at',
            ]);

        // -----------------------------------------------------
        // 9) AVERAGE TIME PER STAGE (in minutes)
        // -----------------------------------------------------
        $avgStageTimesQuery = DB::select('
        SELECT
            h.stages AS stage,
            AVG(TIMESTAMPDIFF(MINUTE, h.changed_at, h_next.next_change)) AS avg_minutes
        FROM (
            SELECT
                ph.id,
                ph.product_id,
                ph.stages,
                ph.changed_at,
                (
                    SELECT MIN(p2.changed_at)
                    FROM product_process_history p2
                    WHERE p2.product_id = ph.product_id
                      AND p2.changed_at > ph.changed_at
                ) AS next_change
            FROM product_process_history ph
        ) AS h
        JOIN (
            SELECT
                ph.id,
                (
                    SELECT MIN(p2.changed_at)
                    FROM product_process_history p2
                    WHERE p2.product_id = ph.product_id
                      AND p2.changed_at > ph.changed_at
                ) AS next_change
            FROM product_process_history ph
        ) AS h_next ON h.id = h_next.id
        WHERE h.next_change IS NOT NULL
        GROUP BY h.stages
    ');

        $avgStageTimesFormatted = [];
        foreach ($avgStageTimesQuery as $row) {
            $avgStageTimesFormatted[$row->stage] = round((float) $row->avg_minutes, 1);
        }

        // -----------------------------------------------------
        // 10) RETURN METRICS SUMMARY
        // -----------------------------------------------------
        return [
            'totalProducts' => (int) $totalProducts,
            'stageCounts' => $stageCounts,
            'qcCounts' => $qcCounts,
            'qc_dates' => $dates,
            'qc_stage_series' => $stageSeries,
            'stuckItems' => $stuckItems,
            'avgStageTimes' => $avgStageTimesFormatted,
            'daily_bonding' => $daily_bonding,
            'daily_tape_edge_qc' => $daily_tape_edge_qc,
            'daily_zip_cover_qc' => $daily_zip_cover_qc,
            'daily_packaging' => $daily_packaging,
            'total_packaging' => $total_packaging,
        ];
    }

    public function exportProducts(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->query('daterange');
        $status = $request->query('status');
        $stages = $request->query('stage');
        $defectsPoints = $request->query('defects_points');
        $search = $request->query('search');
        $startDate = null;
        $endDate = null;
        // print_r($request->all());
        // exit;
        $selectedDate = $request->get('daterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        if ($daterange) {
            $filters['start_date'] = $daterange['start_date'] ?? '';
            $filters['end_date'] = $daterange['end_date'] ?? '';
        }

        $metaInfo = [
            'date_range' => $selectedDate ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

        // Base query from product_process_history joined with products
        $query = DB::table('product_process_history as h')
            ->join('products as p', 'p.id', '=', 'h.product_id')
            ->select(
                'p.product_name',
                'p.sku',
                'p.reference_code',
                'p.size',
                'p.qa_code',
                'p.quantity',
                'h.status',
                'h.stages',
                'h.defects_points',
                'p.qc_confirmed_at',
                'p.created_at',
                'p.updated_at'
            );

        // ✅ Filters
        if ($status) {
            $query->where('h.status', $status);
        }

        if ($stages) {
            $query->where('h.stages', $stages);
        }

        if ($defectsPoints) {
            $query->where('h.defects_points', 'like', "%{$defectsPoints}%");
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.reference_code', 'like', "%{$search}%")
                    ->orWhere('p.qa_code', 'like', "%{$search}%");
            });
        }
        // print_r($stages);
        // exit;
        // if ($startDate && $endDate) {
        //     $query->whereBetween('h.created_at', [$startDate, $endDate]);
        // }
        // 📅 Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            // filter by history date if viewing stage data
            $query->whereBetween('h.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // ✅ Get filtered records
        $records = $query->orderByDesc('h.created_at')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No records found for the selected filters.',
            ]);
        }

        // ✅ Prepare data rows
        $dataRows = $records->map(function ($row) {
            $statusNormalized = strtoupper(trim((string) $row->status));
            if ($statusNormalized === 'FAIL') {
                $statusNormalized = 'FAIL';
            }

            // Convert defects_points into readable comma-separated list
            $defects = '';
            if (! empty($row->defects_points)) {
                $decoded = json_decode($row->defects_points, true);
                if (is_array($decoded)) {
                    $defects = implode(', ', $decoded);
                } else {
                    // Handle already-comma or text format
                    $defects = str_replace(['[', ']', '"'], '', $row->defects_points);
                }
            }

            return [
                $row->product_name,
                $row->sku,
                $row->reference_code,
                $row->size,
                $row->qa_code,
                $row->quantity,
                $statusNormalized,
                $row->stages,
                $defects,
                $row->qc_confirmed_at ? LocaleHelper::formatDateWithTime($row->qc_confirmed_at) : '',
                LocaleHelper::formatDateWithTime($row->created_at),
                LocaleHelper::formatDateWithTime($row->updated_at),
            ];
        })->toArray();

        $headers = [
            'Product Name',
            'SKU',
            'Reference Code',
            'Size',
            'QA Code',
            'Quantity',
            'Status',
            'Stage',
            'Defect Points',
            'QC Confirmed At',
            'Created At',
            'Updated At',
        ];

        return Excel::download(
            new ProductExport($dataRows, $metaInfo, $headers),
            'recentProcessActivity_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
