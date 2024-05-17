<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $with = ['promoter', 'competitorBrand', 'refusedReasons', 'outlet', 'nationality'];

    protected $casts = [
        'user_id' => 'integer',
        'outlet_id' => 'integer',
        'competitor_brand_id' => 'integer',
        'franchise' => 'boolean',
        'did_he_switch' => 'boolean',
        'packs' => 'integer',
        'aspen' => 'array',
        'nationality_id' => 'integer',
    ];


    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function competitorBrand(): BelongsTo
    {
        return $this->belongsTo(CompetitorBrand::class);
    }

    public function refusedReasons()
    {
        return $this->belongsToMany(RefusedReason::class, 'consumer_reason_for_refusal', 'consumer_id', 'refused_reason_id')->withPivot('other_refused_reason');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }
}
