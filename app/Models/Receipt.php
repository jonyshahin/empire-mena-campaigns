<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'number',
        'warehouse_id',
        'receipt_date',
        'remarks',
        'status',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'created_by'   => 'integer',
        'receipt_date' => 'date',
        'posted_at'    => 'datetime',
        'status'       => ReceiptStatus::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
