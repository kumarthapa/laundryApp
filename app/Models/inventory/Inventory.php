<?php

namespace App\Models\inventory;

use App\Models\products\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'rfid_tags';

    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * Columns that can be mass assigned
     */
    protected $fillable = [
        'epc_code',
        'tag_code',
        'product_id',
        'location_id',
        'trolley_id',
        'status',
        'mapped_at',
        'last_scanned_at',
        'reader_code',
        'reader_type',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'location_id' => 'integer',
        'trolley_id' => 'integer',
        'mapped_at' => 'datetime',
        'last_scanned_at' => 'datetime',
    ];

    /**
     * Relationship: a tag belongs to a Product
     */
    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Clean, focused search for inventory (rfid_tags) with optional filters, pagination and sorting.
     *
     * Supported $filters keys:
     *  - status (clean|dirty|out|lost|damaged)
     *  - product_id
     *  - sku
     *  - location_id
     *  - trolley_id
     *  - start_date (YYYY-MM-DD or datetime)
     *  - end_date   (YYYY-MM-DD or datetime)
     *
     * @param  string  $search  Keyword to search (epc_code, tag_code, product_name, sku)
     * @param  string  $sort  Column to sort by (whitelisted)
     * @param  string  $order  asc|desc
     * @return \Illuminate\Support\Collection
     */
    public function search(
        string $search = '',
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        string $sort = 'r.created_at',
        string $order = 'desc'
    ) {
        // base query with product join to allow searching product fields
        $query = DB::table('rfid_tags as r')
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->select(
                'r.*',
                'p.product_name',
                'p.sku'
            );

        // keyword search across main columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('r.epc_code', 'like', "%{$search}%")
                    ->orWhere('r.tag_code', 'like', "%{$search}%")
                    ->orWhere('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%");
            });
        }

        // filters
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('r.status', $filters['status']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('r.product_id', intval($filters['product_id']));
        } elseif (! empty($filters['sku'])) {
            $query->where('p.sku', $filters['sku']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('r.location_id', intval($filters['location_id']));
        }

        if (! empty($filters['trolley_id'])) {
            $query->where('r.trolley_id', intval($filters['trolley_id']));
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('r.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // protect sort column against injection by allowing only a whitelist
        $allowedSorts = [
            'r.created_at', 'r.epc_code', 'r.tag_code', 'r.last_scanned_at',
            'p.product_name', 'p.sku', 'r.status',
        ];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'r.created_at';
        }

        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';
        // print_r($query->get());
        // exit;

        return $query->orderBy($sort, $order)
            ->limit(intval($limit))
            ->offset(intval($offset))
            ->get();
    }

    /**
     * Count matching rows for pagination.
     */
    public function get_found_rows(string $search = '', array $filters = []): int
    {
        $query = DB::table('rfid_tags as r')
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->select('r.id');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('r.epc_code', 'like', "%{$search}%")
                    ->orWhere('r.tag_code', 'like', "%{$search}%")
                    ->orWhere('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('r.status', $filters['status']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('r.product_id', intval($filters['product_id']));
        } elseif (! empty($filters['sku'])) {
            $query->where('p.sku', $filters['sku']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('r.location_id', intval($filters['location_id']));
        }

        if (! empty($filters['trolley_id'])) {
            $query->where('r.trolley_id', intval($filters['trolley_id']));
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('r.created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return (int) $query->count();
    }

    /**
     * Backwards-compatible wrapper for stock report retrieval.
     */
    public function getStockReport($search = '', $filters = [], $limit = 100, $offset = 0, $sort = 'r.created_at', $order = 'desc')
    {
        return $this->search($search, $filters, $limit, $offset, $sort, $order);
    }

    public function getStockReportCount($search = '', $filters = [])
    {
        return $this->get_found_rows($search, $filters);
    }
}
