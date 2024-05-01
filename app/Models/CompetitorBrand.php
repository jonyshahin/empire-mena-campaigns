<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompetitorBrand extends Model
{
    use HasFactory;

    protected $table = 'competitor_brands';

    protected $guarded = [];

    public function consumer(): HasOne
    {
        return $this->hasOne(Consumer::class);
    }
}
