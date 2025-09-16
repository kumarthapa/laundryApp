<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\products\Products;
use App\Models\products\ProductProcessHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    protected $products;

    public function __construct(Products $products)
    {
        $this->products = $products;
        // Uncomment if you want to protect endpoints with sanctum
        // $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/dashboard/summary
     * Accepts optional ?cache_seconds= (int) to override default cache duration.
     */
    public function summary(Request $request)
    {
        $cacheSeconds = (int) $request->query('cache_seconds', 5); // default cache for polling

        // Use date in cache key so caches automatically change at midnight
        $todayKey = Carbon::today()->toDateString();
        $cacheKey = "dashboard.summary.v1.{$todayKey}";

        $data = Cache::remember($cacheKey, $cacheSeconds, function () {
            $todayStart = Carbon::today()->startOfDay();
            $todayEnd = Carbon::today()->endOfDay();
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();

            // KPIs
            $totalToday = (int) $this->products->whereBetween('created_at', [$todayStart, $todayEnd])->count();
            $totalMonth = (int) $this->products->whereBetween('created_at', [$monthStart, $monthEnd])->count();

            $defectsToday = (int) $this->products->whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('qc_status', 'FAILED')->count();

            $goodToday = (int) $this->products->whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('qc_status', 'PASS')->count();

            $pendingToday = (int) $this->products->whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('qc_status', 'PENDING')->count();

            $efficiency = $totalToday > 0 ? round(($goodToday / $totalToday) * 100, 2) : 0.0;
            $defectRate = $totalToday > 0 ? round(($defectsToday / $totalToday) * 100, 2) : 0.0;

            // In-progress counts grouped by current_stage
            $stagesCounts = $this->products->select('current_stage', DB::raw('count(*) as cnt'))
                ->groupBy('current_stage')
                ->orderBy('cnt', 'desc')
                ->get()
                ->pluck('cnt', 'current_stage')
                ->map(function ($v) { return (int)$v; })
                ->toArray();

            // Recent activities from process history (latest 20)
            $recent = ProductProcessHistory::with('product')
                ->orderBy('changed_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($r) {
                    return [
                        'product_id' => (int) $r->product_id,
                        'product_name' => optional($r->product)->product_name,
                        'rfid_tag' => optional($r->product)->rfid_tag,
                        'stage' => $r->stage,
                        'status' => $r->status,
                        'comments' => $r->comments,
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
     * Returns counts grouped by stage as plain array
     */
    public function stages()
    {
        $stagesCounts = $this->products->select('current_stage', DB::raw('count(*) as cnt'))
            ->groupBy('current_stage')
            ->orderBy('cnt', 'desc')
            ->get()
            ->pluck('cnt', 'current_stage')
            ->map(function ($v) { return (int)$v; })
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $stagesCounts,
        ]);
    }

    /**
     * GET /api/dashboard/recent-activities
     * Optional query param: limit (default 20)
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
                    'stage' => $r->stage,
                    'status' => $r->status,
                    'comments' => $r->comments,
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
