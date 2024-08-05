<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CompetitorBrand extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'competitor_brands';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $append = [
        'logo'
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

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
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
        $this
            ->addMediaCollection('logo')
            ->singleFile();
    }
}
