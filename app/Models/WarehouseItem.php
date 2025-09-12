<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseItem extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'on_hand',
        'reserved',
    ];

    protected $casts = [
        'on_hand'  => 'decimal:6',
        'reserved' => 'decimal:6',
    ];

    /* =======================
     | Relationships
     |======================= */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /* =======================
     | Accessors / Helpers
     |======================= */

    public function getAvailableAttribute(): float
    {
        return (float) $this->on_hand - (float) $this->reserved;
    }

    /* =======================
     | Scopes
     |======================= */

    public function scopeOfWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeOfProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
