<?php

namespace App\Models\products;

use App\Helpers\LocaleHelper;
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
        'is_locked',
        'locked_by',
        'location_id',
    ];

    protected $dates = [
        'write_date',
        'qc_confirmed_at',
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
    public function search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc')
    {
        $query = DB::table($this->table.' as b')
            ->select('b.*');

        // Search only on bonding_plan_products columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('b.qa_code', 'like', "%{$search}%")
                    ->orWhere('b.model', 'like', "%{$search}%")
                    ->orWhere('b.bonding_name', 'like', "%{$search}%")
                    ->orWhere('b.sku', 'like', "%{$search}%")
                    ->orWhere('b.size', 'like', "%{$search}%")
                    ->orWhere('b.product_name', 'like', "%{$search}%");
            });
        }
        // Apply location filter (for user)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'b');

        // Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('b.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // Status filter (0, 1, or all)
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('b.is_write', $filters['status']);
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
        $query = DB::table($this->table.' as b')
            ->select('b.id');

        // Apply search on bonding_plan_products only
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('b.qa_code', 'like', "%{$search}%")
                    ->orWhere('b.model', 'like', "%{$search}%")
                    ->orWhere('b.bonding_name', 'like', "%{$search}%")
                    ->orWhere('b.sku', 'like', "%{$search}%")
                    ->orWhere('b.size', 'like', "%{$search}%")
                    ->orWhere('b.product_name', 'like', "%{$search}%");
            });
        }
        // Apply location filter (for user)
        $query = LocaleHelper::commonWhereLocationCheck($query, 'b');
        // Date range filter
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('b.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // Status filter
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('b.is_write', $filters['status']);
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
