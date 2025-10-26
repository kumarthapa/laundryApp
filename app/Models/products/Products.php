<?php

namespace App\Models\products;

use App\Helpers\LocaleHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Products extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'products';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'bonding_plan_product_id',
        'product_name',
        'rfid_tag',
        'qa_code',
        'sku',
        'reference_code',
        'size',
        'quantity',
        'qc_confirmed_at',
        'qc_status_updated_by',
        'location_id',
    ];

    protected $dates = [
        'qc_confirmed_at',
        'created_at',
        'updated_at',
    ];

    /* ---------------------------
     | Relationships
     |---------------------------*/
    public function bondingPlanProduct()
    {
        return $this->belongsTo(BondingPlanProduct::class, 'bonding_plan_product_id');
    }

    public function processHistory()
    {
        return $this->hasMany(ProductProcessHistory::class, 'product_id');
    }

    /**
     * Latest history record (by changed_at)
     */
    public function latestHistory()
    {
        return $this->hasOne(ProductProcessHistory::class, 'product_id')->latestOfMany('changed_at');
    }

    /* ---------------------------
     | Queries / Reports
     |---------------------------*/

    /**
     * Search products with filters, pagination, and sorting.
     * Uses latest entry from product_process_history (by changed_at) via a correlated subquery.
     *
     * @param  string  $search
     * @param  array  $filters  (expects keys: 'status'|'qc_status', 'stages', 'defects_points', 'start_date', 'end_date')
     * @param  int  $limit
     * @param  int  $offset
     * @param  string  $sort
     * @param  string  $order
     * @return \Illuminate\Support\Collection
     */
    public function search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'p.created_at', $order = 'desc')
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
                'p.*',
                // alias status to qc_status for backward compatibility (many places expect qc_status)
                'h.status as status',
                'h.stages as stages',
                'h.defects_points',
                'h.changed_at as last_changed_at'
            );

        // Search across product columns and history fields
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
        // USER LOCATION CHECK
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');

        // Stage filter (match stages string from history)
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', $filters['stages']);
        }

        // QC/Status filter — accept both 'status' and legacy 'qc_status' keys
        $statusFilter = $filters['status'] ?? ($filters['qc_status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $filterQc = strtoupper($statusFilter);
            if ($filterQc === 'FAIL') {
                $filterQc = 'FAIL';
            }
            $query->where('h.status', $filterQc);
        }

        // Defect points filter (stored as JSON array in h.defects_points)
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            // Use binding with json_encode for safety. JSON_CONTAINS expects a JSON value,
            // so json_encode('colour_issue') => "\"colour_issue\"" which is correct.
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }
        // Date range filter (product created_at)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // Protect sort/order inputs a bit
        $allowedOrders = ['asc', 'desc'];
        $order = in_array(strtolower($order), $allowedOrders) ? $order : 'desc';
        // Apply sort, limit, offset
        $query->orderBy($sort, $order)
            ->limit(intval($limit))
            ->offset(intval($offset));

        // print_r($query->get());
        // exit;
        return $query->get();
    }

    /**
     * Count matching rows for pagination.
     *
     * @param  string  $search
     * @param  array  $filters
     * @return int
     */
    public function get_found_rows($search = '', $filters = [])
    {
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
            ->select('p.id');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('p.size', 'like', "%{$search}%")
                    ->orWhere('p.rfid_tag', 'like', "%{$search}%")
                    ->orWhere('h.status', 'like', "%{$search}%")
                    ->orWhere('h.stages', 'like', "%{$search}%")
                    ->orWhere('h.defects_points', 'like', "%{$search}%");
            });
        }
        // USER LOCATION CHECK
        $query = LocaleHelper::commonWhereLocationCheck($query, 'p');
        // Stage filter
        if (! empty($filters['stages']) && $filters['stages'] !== 'all') {
            $query->where('h.stages', $filters['stages']);
        }

        // QC/Status filter (accept 'qc_status' too)
        $statusFilter = $filters['qc_status'] ?? ($filters['status'] ?? null);
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $filterQc = strtoupper($statusFilter);
            if ($filterQc === 'FAIL') {
                $filterQc = 'FAIL';
            }
            $query->where('h.status', $filterQc);
        }

        // Defect points filter (JSON)
        if (! empty($filters['defects_points']) && $filters['defects_points'] !== 'all') {
            $jsonVal = json_encode($filters['defects_points']);
            $query->whereRaw('JSON_CONTAINS(h.defects_points, ?)', [$jsonVal]);
        }

        // Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('p.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->count();
    }
}
