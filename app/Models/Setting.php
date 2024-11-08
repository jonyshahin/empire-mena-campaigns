<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_setting')->withPivot(['value']);
    }
}
