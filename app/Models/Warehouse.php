<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'code',
        'location',
        'manager_id',
        'is_active',
        'district_id',
        'zone_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'manager_id' => 'integer',
        'district_id' => 'integer',
        'zone_id' => 'integer',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'warehouse_items', 'warehouse_id', 'product_id')
            ->withPivot(['on_hand', 'reserved'])
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(Adjustment::class);
    }

    public function outboundTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function inboundTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    /* ============================================================
     | Query Scopes
     |============================================================ */

    /** Scope: only active warehouses. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: simple search by name/code/location. */
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $like = '%' . trim($term) . '%';
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('location', 'like', $like);
        });
    }

    /* ============================================================
     | Helpers
     |============================================================ */

    /**
     * Get current on_hand quantity for a given Product in this warehouse.
     *
     * @param  int|Product  $product
     * @return float
     */
    public function onHand($product): float
    {
        $productId = $product instanceof Product ? $product->getKey() : (int) $product;

        $row = $this->items()
            ->where('product_id', $productId)
            ->first(['on_hand']);

        return (float) ($row->on_hand ?? 0);
    }

    /**
     * Get current reserved quantity for a given Product in this warehouse.
     *
     * @param  int|Product  $product
     * @return float
     */
    public function reserved($product): float
    {
        $productId = $product instanceof Product ? $product->getKey() : (int) $product;

        $row = $this->items()
            ->where('product_id', $productId)
            ->first(['reserved']);

        return (float) ($row->reserved ?? 0);
    }

    /**
     * Quick accessor for a human-friendly display label.
     */
    public function getDisplayLabelAttribute(): string
    {
        $parts = array_filter([$this->code, $this->name, $this->location]);
        return implode(' — ', $parts);
    }
}
