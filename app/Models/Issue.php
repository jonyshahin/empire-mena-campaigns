<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'number',
        'warehouse_id',
        'issue_date',
        'remarks',
        'status',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'created_by'   => 'integer',
        'issue_date' => 'date',
        'posted_at'  => 'datetime',
        'status'     => IssueStatus::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(IssueItem::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
