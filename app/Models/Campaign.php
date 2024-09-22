<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'promoters',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_product');
    }

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campaign_promoter')->without(['campaigns']);
    }
}
