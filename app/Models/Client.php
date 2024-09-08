<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Client extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_name',
        'contact_person',
        'website',
        'phone',
        'address',
        'hq_map_name',
        'hq_map_url',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [
        'media',
        'industries',
        'brands',
    ];

    protected $append = [
        'logo',
        'cover_image'
    ];

    public function getLogoAttribute()
    {
        $avatar = $this->getFirstMedia('logo');
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

    public function getCoverImageAttribute()
    {
        $avatar = $this->getFirstMedia('cover_image');
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

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('cover_image')->singleFile();
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function industries()
    {
        return $this->belongsToMany(Industry::class, 'client_industry');
    }

    public function brands()
    {
        return $this->belongsToMany(CompetitorBrand::class, 'brand_client', 'client_id', 'brand_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'client_user', 'client_id', 'user_id');
    }
}
