<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Traversable;

class Consumer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $with = [
        'promoter',
        'competitorBrand',
        'refusedReasons',
        'outlet',
        'nationality',
        'campaign',
        'competitor_product',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'outlet_id' => 'integer',
        'competitor_brand_id' => 'integer',
        'franchise' => 'boolean',
        'did_he_switch' => 'boolean',
        'packs' => 'integer',
        'aspen' => 'array',
        'nationality_id' => 'integer',
        'campaign_id' => 'integer',
        'selected_products' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'dynamic_incentives' => 'array',
    ];

    protected function packs(): Attribute
    {
        return Attribute::make(
            get: function () {
                $selected_products = $this->selected_products;
                $packs = 0;
                if (is_array($selected_products) || $selected_products instanceof Traversable) {
                    foreach ($selected_products as $product) {
                        if (is_array($product) && array_key_exists('packs', $product)) {
                            $packs += intval($product['packs']);
                        }
                    }
                }
                return $packs;
            },
        );
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function competitorBrand(): BelongsTo
    {
        return $this->belongsTo(CompetitorBrand::class);
    }

    public function refusedReasons()
    {
        return $this->belongsToMany(RefusedReason::class, 'consumer_reason_for_refusal', 'consumer_id', 'refused_reason_id')->withPivot('other_refused_reason');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function district(): BelongsTo
    {
        return $this->outlet->district();
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('telephone', 'like', "%{$search}%")
            ->orWhere('other_brand_name', 'like', "%{$search}%")
            ->orWhere('aspen', 'like', "%{$search}%")
            ->orWhere('packs', 'like', "%{$search}%")
            ->orWhere('incentives', 'like', "%{$search}%")
            ->orWhere('age', 'like', "%{$search}%")
            ->orWhere('gender', 'like', "%{$search}%")
            ->orWhereDate('created_at', 'like', "%{$search}%")
            ->orWhereHas('promoter', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orWhereHas('competitorBrand', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orWhereHas('outlet', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%")
                    ->orWhereHas('district', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('zone', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            })
            ->orWhereHas('nationality', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone('Asia/Baghdad');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone('Asia/Baghdad');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function competitor_product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'competitor_product_id', 'id');
    }
}
