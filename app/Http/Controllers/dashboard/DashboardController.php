<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\products\Products;
use App\Helpers\UtilityHelper; // Assuming this is where getProductStagesAndDefectPoints is defined

class DashboardController extends Controller
{
  public function index(Request $request)
  {
    $metrics = $this->gatherMetrics();
    return view('content.dashboard.dashboards-analytics', [
      'metrics' => $metrics
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

    // Load config arrays for stages and defect points (if needed later)
    $productConfig = UtilityHelper::getProductStagesAndDefectPoints();
    $stagesConfig = $productConfig['stages'] ?? [];
    // (Optionally use $productConfig['defect_points'] if needed)

    // 2) Stage distribution (group by latest history entry's 'stages' field)
    // Subquery to get the latest history ID for each product
    $latestHistorySubquery = DB::table('product_process_history')
      ->select('product_id', DB::raw('MAX(id) as max_id'))
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
      ->select(DB::raw("DATE(changed_at) as day"), DB::raw("COUNT(*) as cnt"))
      ->where('changed_at', '>=', $startDate)
      ->groupBy(DB::raw('DATE(changed_at)'))
      ->orderBy('day')
      ->get();

    // Format daily series as date => count (fill missing days with 0)
    $dailySeries = [];
    for ($d = 0; $d < 30; $d++) {
      $day = $startDate->copy()->addDays($d)->toDateString();
      $dailySeries[$day] = 0;
    }
    foreach ($dailyThroughputRows as $r) {
      $dailySeries[$r->day] = (int)$r->cnt;
    }

    // 5) Recent activity (last 20 events)
    $recentActivities = DB::table('product_process_history as h')
      ->join('products as p', 'p.id', '=', 'h.product_id')
      ->select(
        DB::raw("DATE_FORMAT(h.changed_at, '%Y-%m-%d %H:%i:%s') as changed_at"),
        'p.sku',
        'p.product_name',
        'p.rfid_tag',
        'h.stages as stage',
        'h.status as status',
        'h.remarks as comments'
      )
      ->orderBy('h.changed_at', 'desc')
      ->limit(20)
      ->get();

    // 6) QC pass rate (across all QC stages from config)
    // Identify all stages considered QC from config (assuming 'value' contains 'qc')
    $qcStageValues = [];
    foreach ($stagesConfig as $stage) {
      if (stripos($stage['value'], 'qc') !== false) {
        $qcStageValues[] = $stage['value'];
      }
    }
    $qcPassRate = null;
    if (!empty($qcStageValues)) {
      $totalQcChecked = DB::table('product_process_history')
        ->whereIn('stages', $qcStageValues)
        ->count();
      $qcPassCount = DB::table('product_process_history')
        ->whereIn('stages', $qcStageValues)
        ->where('status', 'PASS')
        ->count();
      $qcPassRate = $totalQcChecked > 0
        ? round(($qcPassCount / $totalQcChecked) * 100, 1)
        : null;
    }

    // 7) Stuck items (not progressed in last N days; default 7)
    $daysStuck = 7;
    $stuckCutoff = Carbon::now()->subDays($daysStuck);
    $stuckItems = DB::table('products as p')
      ->joinSub($latestHistorySubquery, 'latest_h', function ($join) {
        $join->on('p.id', '=', 'latest_h.product_id');
      })
      ->join('product_process_history as h', 'h.id', '=', 'latest_h.max_id')
      ->where('p.updated_at', '<', $stuckCutoff)
      ->whereNotIn('h.stages', ['Shipped', 'Ready for Shipment', 'Returned', 'Cancelled'])
      ->orderBy('p.updated_at', 'asc')
      ->limit(20)
      ->get([
        'p.id',
        'p.sku',
        'p.product_name',
        'h.stages as current_stage',
        'p.updated_at'
      ]);

    // 8) Average time per stage (in minutes)
    // We group by the 'stages' field from history.
    $avgStageTimesQuery = DB::select("
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
        ");

    $avgStageTimesFormatted = [];
    foreach ($avgStageTimesQuery as $row) {
      $avgStageTimesFormatted[$row->stage] = round((float)$row->avg_minutes, 1);
    }

    return [
      'totalProducts'  => (int) $totalProducts,
      'stageCounts'    => $stageCounts,
      'qcCounts'       => $qcCounts,
      'dailySeries'    => $dailySeries,
      'recentActivities' => $recentActivities,
      'qcPassRate'     => $qcPassRate,
      'stuckItems'     => $stuckItems,
      'avgStageTimes'  => $avgStageTimesFormatted,
    ];
  }
}