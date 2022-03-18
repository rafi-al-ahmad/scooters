<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductCombination extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sku',
        'product_id',
        'price',
        'variants',
        'discount_percent',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'variants' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'media'
    ];


    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'media',
    ];

    protected $appends = [
        'image',
    ];

    public function getImageAttribute()
    {

        $image = [];

        if ($this->media->first()) {
            $media = $this->media->first();
            $image['id'] = $media->id;
            $image['file_name'] = $media->file_name;
            $image['mime_type'] = $media->mime_type;
            $image['size'] = $media->size;
            $image['url'] = $media->getUrl();
            $image['srcset'] = $media->getSrcset();
        }
        return $image;
    }

}
