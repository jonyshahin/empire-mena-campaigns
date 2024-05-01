<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefusedReason extends Model
{
    use HasFactory;

    protected $table = 'refused_reasons';

    protected $guarded = [];

    public function consumers()
    {
        return $this->belongsToMany(Consumer::class, 'consumer_reason_for_refusal', 'refused_reason_id', 'consumer_id')->withPivot('other_refused_reason');
    }
}
