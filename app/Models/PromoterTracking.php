<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoterTracking extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
