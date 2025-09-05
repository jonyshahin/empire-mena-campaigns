<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductCategory extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'parent_id',
        'media',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'image',
        'icon',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the image attribute.
     *
     * @return array|null
     */
    public function getImageAttribute()
    {
        $avatar = $this->getFirstMedia('image');
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

    /**
     * Get the icon attribute.
     *
     * @return array|null
     */
    public function getIconAttribute()
    {
        $avatar = $this->getFirstMedia('icon');
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

    /**
     * Register the conversions that should be performed on media files.
     *
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    /**
     * Register the media collections.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('icon')->singleFile();
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')
            ->with(['children' => function ($query) {
                $query->without('parent'); // Load children without their parent to avoid loops
            }]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id')
            ->with(['parent' => function ($query) {
                $query->without('children'); // Load parent without children to avoid loops
            }]);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
