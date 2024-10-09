<?php

namespace Winter\User\Models;

use Model;

/**
 * Event Model
 */
class Event extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'winter_user_events_table';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['title', 'color', 'start_time'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'title' => 'required|string|max:255',
        'color' => 'required|string|max:7',
        'start_time' => 'required|date',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'start_time' => 'datetime',
    ];
}
