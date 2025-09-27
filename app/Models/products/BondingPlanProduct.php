<?php

namespace App\Models\products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BondingPlanProduct extends Model
{
    use HasFactory;

    protected $table = 'bonding_plan_products';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'sku',
        'product_name',
        'model',
        'qa_code',
        'size',
        'date',
        'month',
        'year',
        'serial_no',
        'contractor',
        'bonding_name',
        'quantity',
        'is_write',
        'write_by',
        'write_date',
        'reference_code',
    ];

    protected $dates = [
        'write_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Products created from this plan (one-to-many).
     */
    public function products()
    {
        return $this->hasMany(Products::class, 'bonding_plan_product_id');
    }

    /**
     * Search bonding plan products with optional filters, pagination, and sorting.
     */
    public function search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'b.created_at', $order = 'desc')
    {
        // Subquery to get latest history per product (optimized)
        $latestHistory = DB::table('product_process_history as h1')
            ->select('h1.*')
            ->join(DB::raw('(SELECT product_id, MAX(changed_at) AS latest
                             FROM product_process_history
                             GROUP BY product_id) as h2'), function ($join) {
                $join->on('h1.product_id', '=', 'h2.product_id')
                    ->on('h1.changed_at', '=', 'h2.latest');
            });

        $query = DB::table('bonding_plan_products as b')
            ->leftJoin('products as p', 'b.sku', '=', 'p.sku')
            ->leftJoinSub($latestHistory, 'h', function ($join) {
                $join->on('h.product_id', '=', 'p.id');
            })
            ->select(
                'b.*',
                'b.product_name',
                'b.model',
                'b.size',
                'p.rfid_tag',
                'h.status as qc_status',
                'h.status as status',
                'h.stages as current_stage',
                'h.defects_points',
                'h.changed_at as last_changed_at'
            );

        // Search filter
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('b.qa_code', 'like', "%{$search}%")
                    ->orWhere('b.model', 'like', "%{$search}%")
                    ->orWhere('b.bonding_name', 'like', "%{$search}%")
                    ->orWhere('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }

        // Stage filter
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', $filters['stages']);
        }

        // QC / Status filter
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $filterQc = strtoupper($statusFilter);
            if ($filterQc === 'FAILED') {
                $filterQc = 'FAIL';
            }
            $query->where('h.status', $filterQc);
        }

        // Defects points filter
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // Date range filter (bonding plan created_at)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('b.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // Validate order
        $allowedOrders = ['asc', 'desc'];
        $order = in_array(strtolower($order), $allowedOrders) ? $order : 'desc';

        return $query->orderBy($sort, $order)
            ->limit(intval($limit))
            ->offset(intval($offset))
            ->get();
    }

    /**
     * Count matching rows for pagination.
     */
    public function get_found_rows($search = '', $filters = [])
    {
        // Reuse the same join structure
        $latestHistory = DB::table('product_process_history as h1')
            ->select('h1.*')
            ->join(DB::raw('(SELECT product_id, MAX(changed_at) AS latest
                             FROM product_process_history
                             GROUP BY product_id) as h2'), function ($join) {
                $join->on('h1.product_id', '=', 'h2.product_id')
                    ->on('h1.changed_at', '=', 'h2.latest');
            });

        $query = DB::table('bonding_plan_products as b')
            ->leftJoin('products as p', 'b.sku', '=', 'p.sku')
            ->leftJoinSub($latestHistory, 'h', function ($join) {
                $join->on('h.product_id', '=', 'p.id');
            })
            ->select('b.id');

        // Apply same filters as search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('b.qa_code', 'like', "%{$search}%")
                    ->orWhere('b.model', 'like', "%{$search}%")
                    ->orWhere('b.bonding_name', 'like', "%{$search}%")
                    ->orWhere('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('p.rfid_tag', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', $filters['stages']);
        }

        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $filterQc = strtoupper($statusFilter);
            if ($filterQc === 'FAILED') {
                $filterQc = 'FAIL';
            }
            $query->where('h.status', $filterQc);
        }

        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('b.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->count();
    }

    /**
     * Stock report methods (for backward compatibility)
     */
    public function getStockReport($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'b.created_at', $order = 'desc')
    {
        return $this->search($search, $filters, $limit, $offset, $sort, $order);
    }

    public function getStockReportCount($search = '', $filters = [])
    {
        return $this->get_found_rows($search, $filters);
    }
}
