<?php

namespace App\Models\reports;

use App\Helpers\LocaleHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReportsModel extends Model
{
    /**
     * Return product rows joined to history.
     *
     * If a stage filter is provided we join to history directly (showing every matching history row).
     * Otherwise we join to the latest history row per product (avoids duplicates).
     *
     * @param  string  $search
     * @param  array  $filters
     * @param  int  $limit
     * @param  int  $offset
     * @param  string  $sort
     * @param  string  $order
     * @param  string  $reportType
     * @return \Illuminate\Support\Collection
     */
    public function reports_search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc', $reportType = '')
    {
        $limit = intval($limit);
        $offset = intval($offset);
        $allowedOrders = ['asc', 'desc'];
        $order = in_array(strtolower($order), $allowedOrders) ? strtolower($order) : 'desc';

        // Decide early if we're filtering by a stage
        $hasStageFilter = ! empty($filters['stages']) && $filters['stages'] !== 'all';

        // Base select (products + history alias)
        $select = [
            'p.id as id',
            'p.product_name',
            'p.sku',
            'p.reference_code',
            'p.size',
            'p.qa_code',
            'p.quantity',
            'p.created_at as product_created_at',
            'h.id as history_id',
            'h.status',
            'h.stages',
            'h.defects_points',
            'h.created_at as process_date',
            'h.changed_at',
            'p.created_at as created_at', // keep this for backward compatibility in views
            'p.updated_at as updated_at',
        ];

        $query = DB::table('products as p')->select($select);

        if ($hasStageFilter) {
            // Show all matching history rows for the requested stage
            $query->join('product_process_history as h', 'h.product_id', '=', 'p.id');
        } else {
            // Join to the latest history row per product (by id) to avoid duplicate product rows
            // Using a scalar subquery that selects the latest history id for the product
            $subLatestId = '(SELECT ch2.id FROM product_process_history ch2 WHERE ch2.product_id = p.id ORDER BY ch2.id DESC LIMIT 1)';
            $query->leftJoin('product_process_history as h', function ($join) use ($subLatestId) {
                $join->on('h.id', '=', DB::raw($subLatestId));
            });
        }

        // Apply location filter (scope)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');

        // Text search across product & history
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('p.qa_code', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }

        // Stage filter (only meaningful when hasStageFilter true, but harmless otherwise)
        if ($hasStageFilter) {
            $query->where('h.stages', $filters['stages']);
        }

        // Status filter (accept 'status' or legacy 'qc_status')
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('h.status', strtoupper((string) $statusFilter));
        } else {
            // Default behaviour (same as your previous code): show PASS entries
            $query->where('h.status', 'PASS');
        }

        // Defect points JSON filter
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // Date range filtering:
        // If stage filter: filter by history event time (changed_at)
        // Otherwise: filter by product creation time (p.created_at)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            if ($hasStageFilter) {
                $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
            } else {
                $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
            }
        }

        // Sorting: choose sensible defaults based on whether stage filter present
        if ($hasStageFilter) {
            // sort by event time (changed_at) or fallback to history.created_at
            if ($sort === 'process_date' || $sort === 'changed_at' || $sort === 'last_changed_at') {
                $query->orderBy('h.changed_at', $order);
            } else {
                // allow sorting by a few product columns or history columns
                $sortable = [
                    'product_name' => 'p.product_name',
                    'sku' => 'p.sku',
                    'size' => 'p.size',
                    'status' => 'h.status',
                    'stages' => 'h.stages',
                    'process_date' => 'h.created_at',
                    'changed_at' => 'h.changed_at',
                    'created_at' => 'p.created_at',
                ];
                $sortColumn = $sortable[$sort] ?? 'h.changed_at';
                $query->orderBy($sortColumn, $order);
            }
        } else {
            // when showing latest-per-product, default to product created_at (for compatibility)
            $sortable = [
                'created_at' => 'p.created_at',
                'product_created_at' => 'p.created_at',
                'product_name' => 'p.product_name',
                'sku' => 'p.sku',
                'size' => 'p.size',
                'status' => 'h.status',
                'stages' => 'h.stages',
                'last_changed_at' => 'h.changed_at',
            ];
            $sortColumn = $sortable[$sort] ?? 'p.created_at';
            $query->orderBy($sortColumn, $order);
        }

        // Pagination
        $query->limit($limit)->offset($offset);

        return $query->get();
    }

    /**
     * Count matching rows for pagination.
     * Behavior mirrors reports_search(): when stage filter is present count history rows; otherwise count products.
     *
     * @param  string  $search
     * @param  array  $filters
     * @return int
     */
    public function get_found_rows($search = '', $filters = [])
    {
        $hasStageFilter = ! empty($filters['stages']) && $filters['stages'] !== 'all';

        if ($hasStageFilter) {
            // Count matching history rows (distinct by history id)
            $query = DB::table('products as p')
                ->join('product_process_history as h', 'h.product_id', '=', 'p.id')
                ->select(DB::raw('COUNT(DISTINCT h.id) as total_rows'));
        } else {
            // Count matching products (latest-history-per-product view)
            // leftJoin to latest history id per product
            $subLatestId = '(SELECT ch2.id FROM product_process_history ch2 WHERE ch2.product_id = p.id ORDER BY ch2.id DESC LIMIT 1)';
            $query = DB::table('products as p')
                ->leftJoin('product_process_history as h', function ($join) use ($subLatestId) {
                    $join->on('h.id', '=', DB::raw($subLatestId));
                })
                ->select(DB::raw('COUNT(DISTINCT p.id) as total_rows'));
        }

        // Apply location scope
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');

        // Text search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('p.qa_code', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }

        // Stage filter
        if ($hasStageFilter) {
            $query->where('h.stages', $filters['stages']);
        }

        // Status filter
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('h.status', strtoupper((string) $statusFilter));
        } else {
            // Default same as reports_search: show PASS
            $query->where('h.status', 'PASS');
        }

        // Defects JSON filter
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            if ($hasStageFilter) {
                $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
            } else {
                $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
            }
        }

        $result = $query->first();

        return $result ? (int) $result->total_rows : 0;
    }

    /**
     * Backwards-compatible alias used in controller for stock reports.
     */
    public function getCommonStockReport($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc', $reportType = '')
    {
        return $this->reports_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
    }

    /**
     * Backwards-compatible alias for count.
     */
    public function getStockReportCount($search = '', $filters = [])
    {
        return $this->get_found_rows($search, $filters);
    }

    /**
     * daily_floor_stock_report_search:
     * - Uses correlated subquery to pick the latest history per product (by id)
     * - Keeps filtering logic consistent
     */
    public function daily_floor_stock_report_search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc', $reportType = '')
    {
        // Correlated subquery returning latest history id for the product
        $subLatestId = '(SELECT ch2.id FROM product_process_history ch2 WHERE ch2.product_id = p.id ORDER BY ch2.id DESC LIMIT 1)';

        $query = DB::table('products as p')
            ->leftJoin('product_process_history as h', function ($join) use ($subLatestId) {
                $join->on('h.id', '=', DB::raw($subLatestId));
            })
            ->select(
                'p.id as id',
                'p.product_name',
                'p.sku',
                'p.reference_code',
                'p.size',
                'p.qa_code',
                'p.quantity',
                'p.created_at as created_at',
                'h.status as status',
                'h.stages as stages',
                'h.defects_points as defects_points',
                'h.changed_at as last_changed_at',
                'p.updated_at as updated_at'
            );

        // Apply location filter
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');

        // search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('p.qa_code', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }

        // stage
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', (string) $filters['stages']);
        }

        // status
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('h.status', strtoupper((string) $statusFilter));
        }

        // defects
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // date filter: keep original behavior (product.created_at)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // safe sorting mapping
        $allowedOrders = ['asc', 'desc'];
        $order = in_array(strtolower($order), $allowedOrders) ? strtolower($order) : 'desc';

        $sortable = [
            'created_at' => 'p.created_at',
            'product_created_at' => 'p.created_at',
            'process_date' => 'h.created_at',
            'last_changed_at' => 'h.changed_at',
            'changed_at' => 'h.changed_at',
            'product_name' => 'p.product_name',
            'sku' => 'p.sku',
            'size' => 'p.size',
            'status' => 'h.status',
            'stages' => 'h.stages',
        ];

        if (preg_match('/^(p|h)\.[a-z0-9_]+$/i', $sort)) {
            $sortColumn = $sort;
        } elseif (isset($sortable[$sort])) {
            $sortColumn = $sortable[$sort];
        } else {
            $sortColumn = 'p.created_at';
        }

        $query->orderBy($sortColumn, $order)
            ->limit(intval($limit))
            ->offset(intval($offset));

        return $query->get();
    }

    /**
     * Get defective products based on filters (returns history rows that have defects)
     */
    public function getDefectiveProducts(array $filters = [])
    {
        $query = DB::table('products as p')
            ->leftJoin('product_process_history as h', 'h.product_id', '=', 'p.id')
            ->select(
                'p.id as id',
                'p.product_name',
                'p.sku',
                'p.size',
                'p.qa_code',
                'p.quantity',
                'h.status as status',
                'h.stages as stages',
                'h.defects_points as defects_points',
                'h.changed_at as last_changed_at'
            );

        // location scope
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');

        // filters
        if (! empty($filters)) {
            if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
                $query->where('h.stages', $filters['stages']);
            }

            if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
                $jsonVal = json_encode($filters['defects_points']);
                $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
            }

            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
            }
        }

        $query->whereNotNull('h.defects_points')
            ->where('h.defects_points', '!=', '[]')
            ->orderBy('h.changed_at', 'desc');

        return $query->get();
    }

    /**
     * Count products whose latest history row is bonding_qc + PASS.
     * Uses correlated subquery that picks latest history id by id DESC.
     */
    public function getFloorStockBondingCount($search = '', $filters = [])
    {
        $subLatestId = '(SELECT ch2.id FROM product_process_history ch2 WHERE ch2.product_id = p.id ORDER BY ch2.id DESC LIMIT 1)';

        $q = DB::table('products as p')
            ->leftJoin('product_process_history as h', function ($join) use ($subLatestId) {
                $join->on('h.id', '=', DB::raw($subLatestId));
            })
            ->select(DB::raw('COUNT(*) as total_rows'))
            ->where('h.stages', 'bonding_qc')
            ->where('h.status', 'PASS');

        // location scope
        $q = LocaleHelper::commonWhereLocationCheck($q, 'p');

        // search filter
        if (! empty($search)) {
            $q->where(function ($qq) use ($search) {
                $qq->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('p.qa_code', 'like', "%{$search}%");
            });
        }

        // product-created date range (optional)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $q->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        $res = $q->first();

        return $res ? (int) $res->total_rows : 0;
    }
}
