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

        // Read from request OR fallback to today
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        // Cache key based on date range
        $cacheKey = "dashboard.summary.v2.{$startDate->toDateString()}_{$endDate->toDateString()}";

        $data = Cache::remember($cacheKey, $cacheSeconds, function () use ($startDate, $endDate) {
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();

            // Total products
            $totalSelected = $this->products->whereBetween('created_at', [$startDate, $endDate])->count();
            $totalMonth = $this->products->whereBetween('created_at', [$monthStart, $monthEnd])->count();

            // Latest status per product
            $latestHistory = DB::table('product_process_history as h')
                ->select('h.product_id', 'h.status', 'h.stages', 'h.changed_at')
                ->join(DB::raw('(SELECT product_id, MAX(changed_at) as latest_change 
                             FROM product_process_history 
                             GROUP BY product_id) latest'), function ($join) {
                    $join->on('h.product_id', '=', 'latest.product_id')
                        ->on('h.changed_at', '=', 'latest.latest_change');
                });

            $latest = DB::table(DB::raw("({$latestHistory->toSql()}) as t"))
                ->mergeBindings($latestHistory);

            // Status counts (within range)
            $defects = (clone $latest)->whereBetween('t.changed_at', [$startDate, $endDate])->where('t.status', 'FAIL')->count();
            $good = (clone $latest)->whereBetween('t.changed_at', [$startDate, $endDate])->where('t.status', 'PASS')->count();
            $pending = (clone $latest)->whereBetween('t.changed_at', [$startDate, $endDate])->where('t.status', 'PENDING')->count();

            $productions = (clone $latest)
                ->whereBetween('t.changed_at', [$startDate, $endDate])
                ->where('t.status', 'PASS')
                ->where('t.stages', 'packaging')
                ->count();

            $efficiency = $totalSelected > 0 ? round(($good / $totalSelected) * 100, 2) : 0.0;
            $defectRate = $totalSelected > 0 ? round(($defects / $totalSelected) * 100, 2) : 0.0;

            // Stage counts
            $stagesCounts = $latest
                ->select('t.stages', DB::raw('COUNT(*) as cnt'))
                ->whereBetween('t.changed_at', [$startDate, $endDate])
                ->groupBy('t.stages')
                ->pluck('cnt', 't.stages')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            // Recent activities (filtered by date range)
            $recent = ProductProcessHistory::with('product')
                ->whereBetween('changed_at', [$startDate, $endDate])
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
                    'total_today' => $productions,
                    'total_month' => $totalMonth,
                    'pass_today' => $good,
                    'pending_selected' => $pending,
                    'defects_selected' => $defects,
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
