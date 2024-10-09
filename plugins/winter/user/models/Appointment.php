<?php
namespace Winter\User\Models;

use Model;

class Appointment extends Model
{
    protected $table = 'winter_user_appointments'; // Укажите правильное имя таблицы

    public $belongsTo = [
        'patient' => ['Winter\User\Models\User', 'key' => 'patient_id'], // Связь с пациентом
        'doctor' => ['Winter\User\Models\User', 'key' => 'doctor_id']    // Связь с врачом
    ];
}