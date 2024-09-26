<?php namespace Winter\User\Models;

use Model;

class VisitHistory extends Model
{
    protected $table = 'winter_user_visit_histories'; // Правильное название таблицы

    protected $fillable = ['patient_id', 'doctor_id', 'visit_notes', 'visit_date'];

    public $belongsTo = [
        'patient' => ['Winter\User\Models\User', 'key' => 'patient_id'],
        'doctor' => ['Winter\User\Models\User', 'key' => 'doctor_id'],
    ];
}
