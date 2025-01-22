<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incentive extends Model
{
    use HasFactory;

    // Define fillable attributes
    protected $fillable = [
        'campaign_id',
        'brand_id',
        'name',
        'value',
    ];

    // Define casts
    protected $casts = [
        'campaign_id' => 'integer',
        'brand_id' => 'integer',
        'name' => 'string',
        'value' => 'integer',
    ];

    // Define relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CompetitorBrand::class, 'brand_id');
    }
}
