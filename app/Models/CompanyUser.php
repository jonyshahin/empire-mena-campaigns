<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = [
        'client_id',
        'user_id',
    ];

    protected $with = ['client', 'user'];


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
