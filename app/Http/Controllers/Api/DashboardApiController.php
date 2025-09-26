<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\products\ProductProcessHistory;
use App\Models\products\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    protected $products;

    public function __construct(Products $products)
    {
        $this->products = $products;
    }

    /**
     * GET /api/dashboard/summary
     */
    public function summary(Request $request)
    {
        $cacheSeconds = (int) $request->query('cache_seconds', 5);
        $todayKey = Carbon::today()->toDateString();
        $cacheKey = "dashboard.summary.v2.{$todayKey}";

        $data = Cache::remember($cacheKey, $cacheSeconds, function () {
            $todayStart = Carbon::today()->startOfDay();
            $todayEnd = Carbon::today()->endOfDay();
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();

            // Total products created
            $totalToday = $this->products->whereBetween('created_at', [$todayStart, $todayEnd])->count();
            $totalMonth = $this->products->whereBetween('created_at', [$monthStart, $monthEnd])->count();

            // Latest status per product
            $latestHistory = DB::table('product_process_history as h')
                ->select('h.product_id', 'h.status', 'h.stages')
                ->join(DB::raw('(SELECT product_id, MAX(changed_at) as latest_change 
                                 FROM product_process_history 
                                 GROUP BY product_id) latest'), function ($join) {
                    $join->on('h.product_id', '=', 'latest.product_id')
                        ->on('h.changed_at', '=', 'latest.latest_change');
                });

            $latest = DB::table(DB::raw("({$latestHistory->toSql()}) as t"))
                ->mergeBindings($latestHistory);

            // Status counts
            $defectsToday = (clone $latest)->where('t.status', 'FAIL')->count();
            $goodToday = (clone $latest)->where('t.status', 'PASS')->count();
            $pendingToday = (clone $latest)->where('t.status', 'PENDING')->count();

            $efficiency = $totalToday > 0 ? round(($goodToday / $totalToday) * 100, 2) : 0.0;
            $defectRate = $totalToday > 0 ? round(($defectsToday / $totalToday) * 100, 2) : 0.0;

            // Stage counts
            $stagesCounts = $latest
                ->select('t.stages', DB::raw('COUNT(*) as cnt'))
                ->groupBy('t.stages')
                ->pluck('cnt', 't.stages')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            // Recent activities
            $recent = ProductProcessHistory::with('product')
                ->orderBy('changed_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($r) {
                    return [
                        'product_id' => (int) $r->product_id,
                        'product_name' => optional($r->product)->product_name,
                        'rfid_tag' => optional($r->product)->rfid_tag,
                        'stage' => $r->stages,
                        'status' => $r->status,
                        'defects' => $r->defects_points,
                        'remarks' => $r->remarks,
                        'changed_at' => $r->changed_at ? Carbon::parse($r->changed_at)->toDateTimeString() : null,
                    ];
                })
                ->toArray();

            return [
                'kpis' => [
                    'total_today' => $totalToday,
                    'total_month' => $totalMonth,
                    'pass_today' => $goodToday,
                    'pending_today' => $pendingToday,
                    'defects_today' => $defectsToday,
                    'efficiency_percent' => $efficiency,
                    'defect_rate_percent' => $defectRate,
                ],
                'stages' => $stagesCounts,
                'recent_activities' => $recent,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary fetched successfully',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/dashboard/stages
     */
    public function stages()
    {
        $latestHistory = DB::table('product_process_history as h')
            ->select('h.product_id', 'h.stages')
            ->join(DB::raw('(SELECT product_id, MAX(changed_at) as latest_change 
                             FROM product_process_history 
                             GROUP BY product_id) latest'), function ($join) {
                $join->on('h.product_id', '=', 'latest.product_id')
                    ->on('h.changed_at', '=', 'latest.latest_change');
            });

        $latest = DB::table(DB::raw("({$latestHistory->toSql()}) as t"))
            ->mergeBindings($latestHistory);

        $stagesCounts = $latest
            ->select('t.stages', DB::raw('COUNT(*) as cnt'))
            ->groupBy('t.stages')
            ->pluck('cnt', 't.stages')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $stagesCounts,
        ]);
    }

    /**
     * GET /api/dashboard/recent-activities
     */
    public function recentActivities(Request $request)
    {
        $limit = (int) $request->query('limit', 20);
        $limit = min(100, max(1, $limit));

        $recent = ProductProcessHistory::with('product')
            ->orderBy('changed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($r) {
                return [
                    'product_id' => (int) $r->product_id,
                    'product_name' => optional($r->product)->product_name,
                    'rfid_tag' => optional($r->product)->rfid_tag,
                    'stage' => $r->stages,
                    'status' => $r->status,
                    'defects' => $r->defects_points,
                    'remarks' => $r->remarks,
                    'changed_at' => $r->changed_at ? Carbon::parse($r->changed_at)->toDateTimeString() : null,
                ];
            })
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $recent,
        ]);
    }
}
