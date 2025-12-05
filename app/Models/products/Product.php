<?php

namespace App\Models\products;

use App\Models\inventory\Inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $primaryKey = 'id';

    protected $fillable = [
        'product_name',
        'sku',
        'category',
        'expected_life_cycles',
        'description',
        'status',
        'location_id',
    ];

    protected $casts = [
        'expected_life_cycles' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Relationship: Product has many RFID tags
     */
    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }

    /**
     * Search products with optional filters, pagination and sorting.
     *
     * @param  string  $search  Keyword search (name, code, category, description)
     * @param  array  $filters  Supported keys: 'status', 'category', 'sku', 'start_date', 'end_date'
     * @param  string  $sort  Column to sort by (use fully qualified name like "products.created_at")
     * @param  string  $order  'asc' or 'desc'
     * @return \Illuminate\Support\Collection
     */
    public function search(
        string $search = '',
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        string $sort = 'products.created_at',
        string $order = 'desc'
    ) {
        $query = DB::table('products');

        // Keyword search across important columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filters
        if (isset($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== '') {
            // Accept boolean or 0/1 string
            $query->where('status', intval($filters['status']));
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['sku'])) {
            $query->where('sku', $filters['sku']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        // Sorting and pagination protections
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $order)
            ->limit(intval($limit))
            ->offset(intval($offset));

        return $query->get();
    }

    /**
     * Count matching rows for pagination.
     */
    public function get_found_rows(string $search = '', array $filters = []): int
    {
        $query = DB::table('products');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== '') {
            $query->where('status', intval($filters['status']));
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['sku'])) {
            $query->where('sku', $filters['sku']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return (int) $query->count();
    }
}
