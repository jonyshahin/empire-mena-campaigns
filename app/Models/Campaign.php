<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'budget',
        'client_id',
    ];

    protected $hidden = [
        'client_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'budget' => 'decimal:2',
        'client_id' => 'integer',
    ];

    protected $with = ['client'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
