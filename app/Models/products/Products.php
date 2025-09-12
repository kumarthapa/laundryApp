<?php

namespace App\Models\products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Products extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'product_name',
        'sku',
        'reference_code',
        'size',
        'rfid_tag',
        'quantity',
        'current_stage',
        'qc_status',
        'qc_confirmed_at',
        'qc_status_update_by',
    ];

    /**
     * Search products with filters, pagination, and sorting
     */
    public function search($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc')
    {
        $query = DB::table($this->table)
            ->where(function ($q) use ($search) {
                if ($search) {
                    $q->where('product_name', 'like', "%$search%")
                        ->orWhere('sku', 'like', "%$search%")
                        ->orWhere('size', 'like', "%$search%")
                        ->orWhere('qc_status', 'like', "%$search%")
                        ->orWhere('current_stage', 'like', "%$search%")
                        ->orWhere('rfid_tag', 'like', "%$search%");
                }
            })
            ->when(!empty($filters['current_stage']) && $filters['current_stage'] !== 'all', function ($q) use ($filters) {
                $q->where('current_stage', $filters['current_stage']);
            })
            ->when(!empty($filters['qc_status']) && $filters['qc_status'] !== 'all', function ($q) use ($filters) {
                $q->where('qc_status', $filters['qc_status']);
            })
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            })
            ->orderBy($sort, $order)
            ->limit($limit)
            ->offset($offset);

        return $query->get();
    }

    /**
     * Count rows matching search and filters
     */
    public function get_found_rows($search = '', $filters = [])
    {
        return $this->search($search, $filters)->count();
    }

    /**
     * Fetch Stock Report with filters, sorting, and pagination
     */
    public function getStockReport($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'created_at', $order = 'desc')
    {
        $query = DB::table($this->table)
            ->select('product_name', 'sku', 'quantity', 'current_stage', 'qc_status')
            ->when($search, function ($q) use ($search) {
                $q->where('product_name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            })
            ->when(!empty($filters['current_stage']) && $filters['current_stage'] !== 'all', function ($q) use ($filters) {
                $q->where('current_stage', $filters['current_stage']);
            })
            ->when(!empty($filters['qc_status']) && $filters['qc_status'] !== 'all', function ($q) use ($filters) {
                $q->where('qc_status', $filters['qc_status']);
            })
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            })
            ->orderBy($sort, $order)
            ->limit($limit)
            ->offset($offset);

        return $query->get();
    }

    /**
     * Count rows for Stock Report matching search and filters
     */
    public function getStockReportCount($search = '', $filters = [])
    {
        $query = DB::table($this->table)
            ->when($search, function ($q) use ($search) {
                $q->where('product_name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%");
            })
            ->when(!empty($filters['current_stage']) && $filters['current_stage'] !== 'all', function ($q) use ($filters) {
                $q->where('current_stage', $filters['current_stage']);
            })
            ->when(!empty($filters['qc_status']) && $filters['qc_status'] !== 'all', function ($q) use ($filters) {
                $q->where('qc_status', $filters['qc_status']);
            })
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            });

        return $query->count();
    }

    /**
     * Optional: Relationship with process history
     */
    public function processHistory()
    {
        return $this->hasMany(ProductProcessHistory::class, 'product_id', 'id');
    }
}