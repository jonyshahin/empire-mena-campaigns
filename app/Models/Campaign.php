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
        'company_id',
    ];

    protected $hidden = [
        'client_id',
        'company_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'budget' => 'decimal:2',
        'client_id' => 'integer',
        'company_id' => 'integer',
    ];

    protected $with = [
        'company',
        'products',
    ];

    public function client()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Client::class);
    }

    public function consumers()
    {
        return $this->hasMany(Consumer::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'campaign_product');
    }
}
