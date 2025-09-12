<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptItem extends Model
{
    protected $fillable = [
        'receipt_id',
        'product_id',
        'quantity',
        'uom',
        'remarks',
    ];

    protected $casts = [
        'receipt_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:6',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
