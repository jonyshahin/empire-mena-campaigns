<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nationality extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }
}
