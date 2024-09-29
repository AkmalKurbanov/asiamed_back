<?php

namespace Winter\User\Models;

use Model;
use Winter\User\Models\User;
use Carbon\Carbon;

class VisitHistory extends Model
{
    protected $table = 'winter_user_visit_histories';

    public $belongsTo = [
        'patient' => ['Winter\User\Models\User', 'key' => 'patient_id'],
        'doctor' => ['Winter\User\Models\User', 'key' => 'doctor_id'],
    ];
    protected $fillable = ['patient_id', 'doctor_id', 'visit_date', 'notes'];

    public function beforeCreate()
    {
        $this->visit_date = Carbon::now();
    }
    
    
}
