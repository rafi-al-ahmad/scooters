<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Spatie\Translatable\HasTranslations;

class Collection extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The language that should translated to
     */
    public $displayLanguage;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'collections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent',
        'title',
        'languages',
        'status',
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
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'title',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'languages' => 'array'
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['children'];

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

    public function setDisplyLanguage($local)
    {
        $this->displayLanguage = $local;
    }

    /**
     * get the children of the collection
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent', 'id');
    }
}
