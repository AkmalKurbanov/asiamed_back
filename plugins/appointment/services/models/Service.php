<?php namespace Appointment\Services\Models;

use Model;

/**
 * Model
 */
class Service extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    
    use \Winter\Storm\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'appointment_services_plugin';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

     public $attachOne = [
        'image' => \System\Models\File::class
    ];
}
