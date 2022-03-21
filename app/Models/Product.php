<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasTranslations;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';


    /**
     * The language that should translated to
     */
    public $displayLanguage;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'videos',
        'description',
        'meta_desc',
        'code',
        'warranty',
        'status',
        'brand_id',
        'collection_id',
        'technical_specifications',
        'product_type',
        'options',
        'variant_options',
        'languages',
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
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
        'videos' => 'array',
        'options' => 'array',
        'variant_options' => 'array',
        'technical_specifications' => 'array'
    ];

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'title',
        'description',
        'meta_desc',
        'technical_specifications',
        'variant_options',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'media',
        'variants',
        'features',
        'mainImage'
    ];

    // declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($model) {
             $model->variants()->delete();
             $model->features()->delete();
        });
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

    /**
     * Get the model's meta description by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function metaDesc(): Attribute
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
     * set the language should this model translated to
     */
    public function setDisplyLanguage($local)
    {
        $this->displayLanguage = $local;
    }

    /**
     * prepare the media strcture for this model
     * 
     * @return array
     */
    public function getPreparedMedia()
    {
        $modelMedia = [];

        foreach ($this->media as $key => $mediaItem) {
            $modelMedia[$key]['id'] = $mediaItem->id;
            $modelMedia[$key]['file_name'] = $mediaItem->file_name;
            $modelMedia[$key]['mime_type'] = $mediaItem->mime_type;
            $modelMedia[$key]['size'] = $mediaItem->size;
            $modelMedia[$key]['url'] = $mediaItem->getUrl();
            $modelMedia[$key]['srcset'] = $mediaItem->getSrcset();
        }

        foreach ($this->variants as $variant) {
            if (isset($variant->media)) {
                foreach ($variant->media as $variantMedia) {
                    $modelMedia[] = [
                        "id" => $variantMedia->id,
                        "file_name" => $variantMedia->file_name,
                        "mime_type" => $variantMedia->mime_type,
                        "size" => $variantMedia->size,
                        "url" => $variantMedia->getUrl(),
                        "srcset" => $variantMedia->getSrcset(),
                    ];
                }
            }
        }

        return $modelMedia;
    }

    /**
     * prepare the media strcture for this model
     * 
     * @return array
     */
    public function getMainImage()
    {
        $main_image = [];
        if ($this->mainImage) {
            $main_image['id'] = $this->mainImage->id;
            $main_image['file_name'] = $this->mainImage->file_name;
            $main_image['mime_type'] = $this->mainImage->mime_type;
            $main_image['size'] = $this->mainImage->size;
            $main_image['url'] = $this->mainImage->getUrl();
            $main_image['srcset'] = $this->mainImage->getSrcset();
        }

        return $main_image;
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'product_id', 'id');
    }

    public function features()
    {
        return $this->hasMany(Feature::class, 'product_id', 'id');
    }

    public function mainImage()
    {
        return $this->hasOne(config('media-library.media_model'), 'model_id', 'id')
            ->where('custom_properties->is_default', 1)
            ->where('model_type', static::class);
    }

}
