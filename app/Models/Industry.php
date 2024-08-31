<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_industry');
    }
}
