<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Feature extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'features';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'product_id',
    ];
    
    
    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'title',
        'description',
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'languages' => 'array',
    ];

    
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['media'];

    
    protected $appends = [
        'image',
    ];


    public function getPreparedMedia()
    {
        $modelMedia = [];

        foreach ($this->media as $key => $mediaItem) {
            $modelMedia[$key]['id'] = $mediaItem->id;
            $modelMedia[$key]['file_name'] = $mediaItem->file_name;
            $modelMedia[$key]['mime_type'] = $mediaItem->mime_type;
            $modelMedia[$key]['size'] = $mediaItem->size;
            $modelMedia[$key]['srcset'] = $mediaItem->getSrcset();
            $modelMedia[$key]['url'] = $mediaItem->getUrl();
        }
        return $modelMedia;
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

    /**
     * Get the model's title by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function title(): Attribute
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
                return '';
            }
        );
    }

    /**
     * Get the model's description by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function description(): Attribute
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
                return '';
            }
        );
    }


}
