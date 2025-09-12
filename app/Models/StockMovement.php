<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_type',
        'quantity',
        'uom',
        'reference_type',
        'reference_id',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'quantity'      => 'decimal:6',
        'movement_type' => MovementType::class,
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

    /** Source document (Receipt, Issue, Adjustment, StockTransfer, etc.). */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* =======================
     | Accessors / Helpers
     |======================= */

    /** +qty for inbound, -qty for outbound. */
    public function getSignedQuantityAttribute(): float
    {
        return $this->movement_type->isInbound()
            ? (float) $this->quantity
            : -(float) $this->quantity;
    }

    /* =======================
     | Scopes
     |======================= */

    public function scopeOfWarehouse($q, int $warehouseId)
    {
        return $q->where('warehouse_id', $warehouseId);
    }

    public function scopeOfProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    public function scopeType($q, int|array $types)
    {
        return $q->whereIn('movement_type', (array) $types);
    }

    public function scopeBetweenDates($q, ?string $from, ?string $to)
    {
        if ($from) $q->whereDate('created_at', '>=', $from);
        if ($to)   $q->whereDate('created_at', '<=', $to);
        return $q;
    }
}
