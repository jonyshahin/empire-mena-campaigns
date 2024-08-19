<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoterTracking extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $with = [
        'promoter',
    ];

    protected $appends = [
        'user_is_active',
    ];

    public function getUserIsActiveAttribute()
    {
        //check if updated_at is less than 15 mins
        if ($this->updated_at->greaterThan(now()->subMinutes(15))) {
            return false;
        }
        return true;
    }


    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
