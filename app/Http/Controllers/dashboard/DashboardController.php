<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Helpers\UtilityHelper;
use Carbon\Carbon;
use App\Models\user_management\UsersModel;
use Illuminate\Support\Facades\DB;
use App\Models\products\Products;

class DashboardController extends Controller
{
  public function index(Request $request)
  {
    // Main page - we will pass initial data
    $metrics = $this->gatherMetrics();
    return view('content.dashboard.dashboards-analytics', [
      'metrics' => $metrics
    ]);

    // return view('content.dashboard.dashboards-analytics', $metrics);
  }

  // JSON endpoint for polling / refreshing charts
  public function metrics(Request $request)
  {
    $metrics = $this->gatherMetrics();
    return response()->json($metrics);
  }

  protected function gatherMetrics()
  {
    // 1) Total counts
    $totalProducts = Products::count();

    // 2) Stage distribution
    $stageCountsRaw = Products::select('current_stage', DB::raw('COUNT(*) as cnt'))
      ->groupBy('current_stage')
      ->get()
      ->pluck('cnt', 'current_stage')
      ->toArray();

    // 3) QC status counts
    $qcCountsRaw = Products::select('qc_status', DB::raw('COUNT(*) as cnt'))
      ->groupBy('qc_status')
      ->get()
      ->pluck('cnt', 'qc_status')
      ->toArray();

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
      ->select('h.*', 'p.sku', 'p.product_name', 'p.rfid_tag')
      ->orderBy('h.changed_at', 'desc')
      ->limit(20)
      ->get();

    // 6) QC pass rate
    $totalQcChecked = DB::table('product_process_history')->where('stage', 'QC')->count();
    $qcPass = DB::table('product_process_history')->where('stage', 'QC')->where('status', 'PASS')->count();
    $qcPassRate = $totalQcChecked > 0 ? round(($qcPass / $totalQcChecked) * 100, 2) : null;

    // 7) Stuck items (not progressed in last N days; default 7)
    $daysStuck = 7;
    $stuckCutoff = Carbon::now()->subDays($daysStuck);
    $stuckItems = Products::where('updated_at', '<', $stuckCutoff)
      ->whereNotIn('current_stage', ['Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'])
      ->orderBy('updated_at', 'asc')
      ->limit(20)
      ->get(['id', 'sku', 'product_name', 'current_stage', 'updated_at']);

    // 8) Average time per stage (approx): compute average minutes from this stage to next stage per product
    // We'll use a correlated subquery to find the next changed_at per product.
    $avgStageTimes = DB::select("
            SELECT p1.stage,
                   AVG(TIMESTAMPDIFF(MINUTE, p1.changed_at, next_change)) as avg_minutes
            FROM (
                SELECT ph.id, ph.product_id, ph.stage, ph.changed_at,
                       (SELECT MIN(p2.changed_at)
                        FROM product_process_history p2
                        WHERE p2.product_id = ph.product_id
                          AND p2.changed_at > ph.changed_at) as next_change
                FROM product_process_history ph
            ) p1
            WHERE p1.next_change IS NOT NULL
            GROUP BY p1.stage
        ");

    $avgStageTimesFormatted = [];
    foreach ($avgStageTimes as $row) {
      $avgStageTimesFormatted[$row->stage] = round((float)$row->avg_minutes, 2);
    }

    return [
      'totalProducts' => (int)$totalProducts,
      'stageCounts' => $stageCountsRaw,
      'qcCounts' => $qcCountsRaw,
      'dailySeries' => $dailySeries,
      'recentActivities' => $recentActivities,
      'qcPassRate' => $qcPassRate,
      'stuckItems' => $stuckItems,
      'avgStageTimes' => $avgStageTimesFormatted,
    ];
  }
}