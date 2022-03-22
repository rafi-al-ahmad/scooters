<?php

namespace App\Models;

use App\Events\ApproveAppointmentEvent;
use App\Events\NewAppointmentEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'date',
        'user_id',
        'product_id',
        'service_id',
        'approved',
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
        'date' => 'datetime:Y-m-d H:i',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'service',
        'user',
        'product',
    ];

    
    public static function boot() {
	    parent::boot();

	    static::created(function($model) {
	        event(new NewAppointmentEvent($model));
	    });	 
        static::updated(function($model) {
            if ($model->isDirty('approved') && $model->approved == 1) {
                event(new ApproveAppointmentEvent($model));
            }
        });   
	}

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
