<?php

namespace App\Models\reports;

use App\Helpers\LocaleHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReportsModel extends Model
{
    /**
     * Return all product history rows joined to products, with filtering, sorting & pagination.
     *
     * Note: selects p.created_at as created_at to keep compatibility with controllers/views
     * that expect $row->created_at to be product created date.
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
        // $reportType = 'monthly_yearly_report';
        // exit;
        $query = DB::table('products as p')
            ->join('product_process_history as h', 'h.product_id', '=', 'p.id')
            ->select(
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
                'p.created_at as created_at',
                'p.updated_at as updated_at',
            );
        // Apply location filter (for user)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');
        // 🔍 Text search
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

        // 🎯 Stage filter
        $hasStageFilter = false;
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', $filters['stages']);
            $hasStageFilter = true;
        }

        // 🎯 Status filter
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        // print_r($statusFilter);
        // exit;
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('h.status', strtoupper($statusFilter));
        } else {
            // Default to exclude 'PASS' if no status filter is set
            $query->where('h.status', 'PASS');
        }

        // 🎯 Defect points filter
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // 📅 Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            if ($hasStageFilter) {
                // filter by history date if viewing stage data
                $query->whereBetween('h.created_at', [$filters['start_date'], $filters['end_date']]);
            } else {
                // otherwise filter by product creation date
                $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
            }
        }

        // 🔽 Auto sort based on stage filter
        $allowedOrders = ['asc', 'desc'];
        $order = in_array(strtolower($order), $allowedOrders) ? strtolower($order) : 'desc';

        if ($hasStageFilter) {
            $query->orderBy('h.created_at', $order);
        } else {
            $query->orderBy('p.created_at', $order);
        }
        // print_r($limit);
        // print_r($offset);
        // exit;
        // Apply limit + offset
        $query->limit(intval($limit))->offset(intval($offset));
        // print_r($query->get());
        // exit;

        return $query->get();
    }

    /**
     * Count matching rows for pagination.
     * When joined to history (one-to-many) each matching history row counts separately.
     *
     * @param  string  $search
     * @param  array  $filters
     * @return int
     */
    public function get_found_rows($search = '', $filters = [])
    {
        $query = DB::table('products as p')
            ->join('product_process_history as h', 'h.product_id', '=', 'p.id')
            ->select(DB::raw('COUNT(DISTINCT h.id) as total_rows'));
        // Apply location filter (for user)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');
        // 🔍 Text search
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

        // 🎯 Stage filter
        $hasStageFilter = false;
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', $filters['stages']);
            $hasStageFilter = true;
        }

        // 🎯 Status filter
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('h.status', strtoupper($statusFilter));
        }

        // 🎯 Defect points filter
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // 📅 Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            if ($hasStageFilter) {
                // filter by history date if stage filter is applied
                $query->whereBetween('h.created_at', [$filters['start_date'], $filters['end_date']]);
            } else {
                // otherwise use product created_at
                $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
            }
        }

        $result = $query->first();

        return $result ? (int) $result->total_rows : 0;
    }

    /**
     * Backwards-compatible alias used in controller for stock reports.
     * Uses the same behavior as reports_search (all history rows).
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
     * - Keeps the original "latest history per product" behavior (correlated subquery)
     * - Applies the same filtering logic
     * - Uses safe sort mapping (defaults to product.created_at to match existing UI)
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
    public function daily_floor_stock_report_search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc', $reportType = '')
    {
        // correlated subquery to get the latest changed_at per product
        $subLatest = '(
            SELECT MAX(ch2.changed_at)
            FROM product_process_history ch2
            WHERE ch2.product_id = p.id
        )';

        $query = DB::table('products as p')
            ->leftJoin('product_process_history as h', function ($join) use ($subLatest) {
                $join->on('h.product_id', '=', 'p.id')
                    ->whereRaw("h.changed_at = {$subLatest}");
            })
            ->select(
                'p.id as id',
                'p.product_name',
                'p.sku',
                'p.reference_code',
                'p.size',
                'p.qa_code',
                'p.quantity',
                'p.created_at as created_at', // product created at (used in UI)
                'h.status as status',
                'h.stages as stages',
                'h.defects_points as defects_points',
                'h.changed_at as last_changed_at',
                'p.updated_at as updated_at',
            );
        // Apply location filter (for user)
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

        // stage filter
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', (string) $filters['stages']);
        }

        // status filter
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('h.status', strtoupper((string) $statusFilter));
        }

        // defects filter
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // date filter: original behavior used product.created_at — keep that here
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // safe sorting mapping for this method
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
     * Get defective products based on filters
     */
    public function getDefectiveProducts(array $filters = [])
    {
        // print_r($filters);
        // exit;
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
        // Apply location filter (for user)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');
        // Apply filters if any
        if (! empty($filters)) {
            // Stage filter
            if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
                $query->where('h.stages', $filters['stages']);
            }

            // Defect points filter
            if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
                $jsonVal = json_encode($filters['defects_points']);
                $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
            }

            // Date range filter
            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                $query->whereBetween('h.changed_at', [$filters['start_date'], $filters['end_date']]);
            }
        }
        $query->whereNotNull('h.defects_points')
            ->where('h.defects_points', '!=', '[]')
            ->orderBy('h.changed_at', 'desc'); // descending order

        return $query->get();
    }

    /**
     * Count products whose latest history row is bonding_qc + PASS.
     *
     * @param  string  $search
     * @param  array  $filters
     * @return int
     */
    public function getFloorStockBondingCount($search = '', $filters = [])
    {
        // correlated subquery: latest changed_at per product
        $subLatest = '(
        SELECT MAX(ch2.changed_at)
        FROM product_process_history ch2
        WHERE ch2.product_id = p.id
    )';

        $q = DB::table('products as p')
            ->leftJoin('product_process_history as h', function ($join) use ($subLatest) {
                $join->on('h.product_id', '=', 'p.id')
                    ->whereRaw("h.changed_at = {$subLatest}");
            })
            ->select(DB::raw('COUNT(*) as total_rows'))
            ->where('h.stages', 'bonding_qc')
            ->where('h.status', 'PASS');

        // apply same location scope helper you already use
        $q = LocaleHelper::commonWhereLocationCheck($q, 'p');

        // apply search filter (optional — same fields as other reports)
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
