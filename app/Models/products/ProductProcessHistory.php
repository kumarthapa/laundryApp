<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductProcessHistory extends Model
{
    use HasFactory;

    protected $table = 'product_process_history';

    protected $fillable = [
        'product_id',
        'stages',
        'status',
        'defects_points',
        'changed_at',
        'changed_by',
        'remarks',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'changed_at',
    ];

    /**
     * The product this history row belongs to.
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}