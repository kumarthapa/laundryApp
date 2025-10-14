<?php

namespace App\Http\Controllers\dashboard;

use App\Exports\ProductExport;
use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\products\ProductProcessHistory;
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
        $total_rows = DB::table('product_process_history as h')
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
            })
            ->count();

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
        // 1) Total products
        $totalProducts = Products::count();

        // 1) Today's created products
        $todays_products = Products::with('processHistory')->whereDate('created_at', Carbon::today())->get();
        $todaysTotalFloorProducts = $todays_products->count();

        $daily_bonding = $todays_products->sum(function ($product) {
            return $product->processHistory->where('status', 'PASS')->where('stages', 'bonding_qc')->count();
        });

        $daily_tape_edge_qc = $todays_products->sum(function ($product) {
            return $product->processHistory->where('status', 'PASS')->where('stages', 'tape_edge_qc')->count();
        });

        $daily_zip_cover_qc = $todays_products->sum(function ($product) {
            return $product->processHistory->where('status', 'PASS')->where('stages', 'zip_cover_qc')->count();
        });
        $daily_packaging = $todays_products->sum(function ($product) {
            return $product->processHistory->where('status', 'PASS')->where('stages', 'packaging')->count();
        });

        $total_packaging = ProductProcessHistory::where('stages', 'packaging')->where('status', 'PASS')->count();

        // Load config arrays for stages and defect points (if needed later)
        $productConfig = UtilityHelper::getProductStagesAndDefectPoints();
        $stagesConfig = $productConfig['stages'] ?? [];
        // (Optionally use $productConfig['defect_points'] if needed)

        // 2) Stage distribution (group by latest history entry's 'stages' field)
        // Subquery to get the latest history ID for each product
        $latestHistorySubquery = DB::table('product_process_history')
            ->select('product_id', DB::raw('MAX(id) as max_id'))
            ->where('status', 'PASS')
            ->groupBy('product_id');

        // Count products by their latest stage (using the 'stages' column in history)
        $stageCountsRaw = DB::table('products as p')
            ->joinSub($latestHistorySubquery, 'latest_h', function ($join) {
                $join->on('p.id', '=', 'latest_h.product_id');
            })
            ->join('product_process_history as h', 'h.id', '=', 'latest_h.max_id')
            ->select('h.stages as stage', DB::raw('COUNT(p.id) as cnt'))
            ->groupBy('h.stages')
            ->pluck('cnt', 'stage')
            ->toArray();

        // Optionally, map stage codes to display names (if needed)
        // For example, to use stage 'name' as key:
        // $stageCounts = [];
        // foreach ($stagesConfig as $stage) {
        //     $key = $stage['value'];
        //     $label = $stage['name'];
        //     $stageCounts[$label] = isset($stageCountsRaw[$key]) ? $stageCountsRaw[$key] : 0;
        // }

        // Use raw counts with stage codes as keys:
        $stageCounts = $stageCountsRaw;

        // 3) QC status counts (using 'status' field from history)
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

        // 4) Daily throughput (last 30 days) from product_process_history
        $startDate = Carbon::today()->subDays(29)->startOfDay();
        $dailyThroughputRows = DB::table('product_process_history')
            ->select(DB::raw('DATE(changed_at) as day'), DB::raw('COUNT(*) as cnt'))
            ->where('changed_at', '>=', $startDate)
            ->where('stages', 'bonding_qc') // Exclude packaging stage if needed
            ->groupBy(DB::raw('DATE(changed_at)'))
            ->orderBy('day')
            ->get();

        // Format daily series as date => count (fill missing days with 0)
        $qcEventSeries = [];
        for ($d = 0; $d < 30; $d++) {
            $day = $startDate->copy()->addDays($d)->toDateString();
            $qcEventSeries[$day] = 0;
        }
        foreach ($dailyThroughputRows as $r) {
            $qcEventSeries[$r->day] = (int) $r->cnt;
        }

        // 5) Recent activity (last 20 events)
        // $recentActivities = DB::table('product_process_history as h')
        //     ->join('products as p', 'p.id', '=', 'h.product_id')
        //     ->select(
        //         DB::raw("DATE_FORMAT(h.changed_at, '%Y-%m-%d %H:%i:%s') as changed_at"),
        //         'p.sku',
        //         'p.product_name',
        //         'p.qa_code',
        //         'h.stages as stage',
        //         'h.status as status',
        //         'h.remarks as comments'
        //     )
        //     ->orderBy('h.changed_at', 'desc')
        //     ->limit(30)
        //     ->get();

        // 6) QC pass rate (across all QC stages from config)
        // Identify all stages considered QC from config (assuming 'value' contains 'qc')
        $qcStageValues = [];
        foreach ($stagesConfig as $stage) {
            if (stripos($stage['value'], 'qc') !== false) {
                $qcStageValues[] = $stage['value'];
            }
        }
        // if (! empty($qcStageValues)) {
        //     $totalQcChecked = DB::table('product_process_history')
        //         ->whereIn('stages', $qcStageValues)
        //         ->count();
        //     $qcPassCount = DB::table('product_process_history')
        //         ->whereIn('stages', $qcStageValues)
        //         ->where('status', 'PASS')
        //         ->count();
        // }

        // 7) Stuck items (not progressed in last N days; default 7)
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

        // 8) Average time per stage (in minutes)
        // We group by the 'stages' field from history.
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

        return [
            'totalProducts' => (int) $totalProducts,
            'stageCounts' => $stageCounts,
            'qcCounts' => $qcCounts,
            'qcEventSeries' => $qcEventSeries,
            // 'recentActivities' => $recentActivities,
            'stuckItems' => $stuckItems,
            'avgStageTimes' => $avgStageTimesFormatted,

            'daily_bonding' => $daily_bonding,
            'daily_tape_edge_qc' => $daily_tape_edge_qc,
            'daily_zip_cover_qc' => $daily_zip_cover_qc,
            'daily_packaging' => $daily_packaging,
            'total_packaging' => $total_packaging ?? 0,
        ];
    }

    // public function recentActivities_search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'h.changed_at', $order = 'desc')
    // {
    //     $query = DB::table('product_process_history as h')
    //         ->join('products as p', 'p.id', '=', 'h.product_id')
    //         ->select(
    //             DB::raw("DATE_FORMAT(h.changed_at, '%Y-%m-%d %H:%i:%s') as updated_date"),
    //             'p.sku',
    //             'p.qa_code',
    //             'p.product_name',
    //             'h.stages as stage',
    //             'h.status as status',
    //             'h.defects_points',
    //             'h.remarks as comments'
    //         );

    //     // Search filter
    //     if (! empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('p.product_name', 'like', "%{$search}%")
    //                 ->orWhere('p.sku', 'like', "%{$search}%")
    //                 ->orWhere('p.qa_code', 'like', "%{$search}%")
    //                 ->orWhere('h.status', 'like', "%{$search}%")
    //                 ->orWhere('h.stages', 'like', "%{$search}%")
    //                 ->orWhere('h.remarks', 'like', "%{$search}%")
    //                 ->orWhere('h.defects_points', 'like', "%{$search}%");
    //         });
    //     }

    //     // Stage filter
    //     if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
    //         $query->where('h.stages', $filters['stages']);
    //     }

    //     // Status filter
    //     $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
    //     if (! empty($statusFilter) && $statusFilter !== 'all') {
    //         $query->where('h.status', strtoupper($statusFilter));
    //     }

    //     // Defect points filter (JSON)
    //     if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
    //         $jsonVal = json_encode($filters['defects_points']);
    //         $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
    //     }

    //     // Date range filter (based on updated_date/changed_at)
    //     if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
    //         $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
    //     }

    //     // Order + pagination
    //     $allowedOrders = ['asc', 'desc'];
    //     $order = in_array(strtolower($order), $allowedOrders) ? $order : 'desc';

    //     $query->orderBy($sort, $order)
    //         ->limit(intval($limit))
    //         ->offset(intval($offset));

    //     return $query->get();
    // }

    // public function get_found_rows($search = '', $filters = [])
    // {
    //     $query = DB::table('product_process_history as h')
    //         ->join('products as p', 'p.id', '=', 'h.product_id')
    //         ->select('h.id');

    //     if (! empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('p.product_name', 'like', "%{$search}%")
    //                 ->orWhere('p.sku', 'like', "%{$search}%")
    //                 ->orWhere('p.qa_code', 'like', "%{$search}%")
    //                 ->orWhere('h.status', 'like', "%{$search}%")
    //                 ->orWhere('h.stages', 'like', "%{$search}%")
    //                 ->orWhere('h.remarks', 'like', "%{$search}%")
    //                 ->orWhere('h.defects_points', 'like', "%{$search}%");
    //         });
    //     }

    //     // Stage filter
    //     if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
    //         $query->where('h.stages', $filters['stages']);
    //     }

    //     // Status filter
    //     $statusFilter = $filters['qc_status'] ?? ($filters['status'] ?? null);
    //     if (! empty($statusFilter) && $statusFilter !== 'all') {
    //         $query->where('h.status', strtoupper($statusFilter));
    //     }

    //     // Defect points filter
    //     if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
    //         $jsonVal = json_encode($filters['defects_points']);
    //         $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
    //     }

    //     // Date range filter
    //     if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
    //         $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
    //     }

    //     return $query->count();
    // }

    public function exportProducts(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->query('daterange');
        $status = $request->query('status');
        $stages = $request->query('stages');
        $defectsPoints = $request->query('defects_points');
        $search = $request->query('search');
        $startDate = null;
        $endDate = null;

        if (! empty($daterange)) {
            try {
                [$start, $end] = array_map('trim', explode('-', $daterange));
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', str_replace('-', '/', trim($start)))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', str_replace('-', '/', trim($end)))->endOfDay();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid date range format. Expected 'DD/MM/YYYY - DD/MM/YYYY'.",
                ]);
            }
        }

        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
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

        if ($startDate && $endDate) {
            $query->whereBetween('h.created_at', [$startDate, $endDate]);
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
            'QC Status',
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
