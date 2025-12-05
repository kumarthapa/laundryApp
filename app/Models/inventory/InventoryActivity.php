<?php

namespace App\Models\inventory;

use App\Models\products\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryActivity extends Model
{
    use HasFactory;

    protected $table = 'inventory_activity';

    protected $primaryKey = 'trans_id';

    protected $fillable = [
        'product_id',
        'inventory_id',
        'adjust_qty',
        'inward',
        'outward',
        'opening_stock',
        'closing_stock',
        'trans_type',
        'remarks',
        'status',
        'location_id',
        'created_by',
        'updated_by',
    ];

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'inward' => 'integer',
        'outward' => 'integer',
        'opening_stock' => 'integer',
        'closing_stock' => 'integer',
        'adjust_qty' => 'integer',
        'status' => 'boolean',
    ];

    /* ---------------------------------------------------------
     | Relationships
     ---------------------------------------------------------*/

    /**
     * Product associated with this movement.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * RFID Tag (inventory) mapped in this movement.
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * User who created movement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who updated movement.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ---------------------------------------------------------
     | Scopes
     ---------------------------------------------------------*/

    /**
     * Scope by product.
     */
    public function scopeProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope by location.
     */
    public function scopeLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope by transaction type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('trans_type', $type);
    }

    /* ---------------------------------------------------------
     | Helper Methods for Stock Logic
     ---------------------------------------------------------*/

    /**
     * Automatically set opening & closing stock for every entry.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            // Get last closing stock of this product
            $lastActivity = self::where('product_id', $model->product_id)
                ->orderBy('trans_id', 'DESC')
                ->first();

            $model->opening_stock = $lastActivity->closing_stock ?? 0;

            // Quantity is inward - outward
            $delta = ($model->inward ?? 0) - ($model->outward ?? 0);

            $model->closing_stock = $model->opening_stock + $delta;
        });
    }

    /* ---------------------------------------------------------
     | Static Helper: Log Stock Movement
     | Example usage:
     | InventoryActivity::logMovement($productId, $tagId, 'tag_assign', 1, 'Assigned Tag');
     ---------------------------------------------------------*/

    public static function logMovement(
        int $productId,
        ?int $inventoryId,
        string $type,
        int $inward = 0,
        int $outward = 0,
        ?string $remarks = null,
        ?int $locationId = null,
        ?int $createdBy = null
    ) {
        return self::create([
            'product_id' => $productId,
            'inventory_id' => $inventoryId,
            'inward' => $inward,
            'outward' => $outward,
            'adjust_qty' => $inward > 0 ? $inward : $outward,
            'trans_type' => $type,
            'remarks' => $remarks,
            'location_id' => $locationId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'status' => 1,
        ]);
    }
}
