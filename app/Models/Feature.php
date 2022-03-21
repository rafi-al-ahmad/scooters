<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['media'];



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

}
