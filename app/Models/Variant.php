<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Variant extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;
    
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sku',
        'product_id',
        'options',
        'price',
        'compareAtPrice',
        'quantity',
    ];

    
    /**
     * The language that should translated to
     */
    public $displayLanguage;

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'options',
    ];
    

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'media',
    ];
        
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['media'];

    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'languages' => 'array',
    ];

    
    protected $appends = [
        'image',
    ];

    /**
     * Get the model's options by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function options(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $decodedValue = json_decode($value, true);
                if ($this->displayLanguage) {
                    if (isset($decodedValue[$this->displayLanguage])) {
                        return $decodedValue[$this->displayLanguage];
                    }
                }
                if (isset($decodedValue[App::currentLocale()])) {
                    return $decodedValue[App::currentLocale()];
                }
                if (isset($decodedValue[config('app.fallback_locale')])) {
                    return $decodedValue[config('app.fallback_locale')];
                }
                return null;
            }
        );
    }

    
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
