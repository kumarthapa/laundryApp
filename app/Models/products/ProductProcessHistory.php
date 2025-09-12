<?php

namespace App\Models\products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductProcessHistory extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'product_process_history';
    protected $primaryKey = 'id';
    public $timestamps = false; // We already have `changed_at` timestamp, no need for default timestamps

    protected $fillable = [
        'product_id',
        'stage',
        'status',
        'machine_no',
        'changed_at',
        'changed_by',
        'comments',
    ];

    /**
     * Relationship: each process history belongs to a product
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }

    /**
     * Fetch process history for a specific product
     */
    public static function getHistoryByProduct($productId)
    {
        return self::where('product_id', $productId)
            ->orderBy('changed_at', 'asc')
            ->get();
    }
}
