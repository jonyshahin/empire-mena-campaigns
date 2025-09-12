<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueItem extends Model
{
    protected $fillable = [
        'issue_id',
        'product_id',
        'quantity',
        'uom',
        'remarks',
    ];

    protected $casts = [
        'issue_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:6',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
