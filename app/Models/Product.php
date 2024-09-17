<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sku',
        'product_category_id',
    ];

    protected $hidden = [
        'product_category_id',
        'media',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected $with = ['productCategory'];

    protected $appends = [
        'main_image',
        'images',
    ];

    public function getMainImageAttribute()
    {
        $avatar = $this->getFirstMedia('main_image');
        if (!$avatar) {
            return null;
        }
        return [
            'id' => $avatar->id,
            'url' => $avatar->getUrl(),
            'preview' => $avatar->getUrl('preview'),
            'hash' => $avatar->getCustomProperty('hash'),
            'name' => $avatar->file_name,
        ];
    }

    public function getImagesAttribute()
    {
        return $this->getMedia('images')->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => $image->getUrl(),
                'preview' => $image->getUrl('preview'),
                'hash' => $image->getCustomProperty('hash'),
                'name' => $image->file_name,
            ];
        });
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')->singleFile();
        $this->addMediaCollection('images')->onlyKeepLatest(5);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_product');
    }
}
