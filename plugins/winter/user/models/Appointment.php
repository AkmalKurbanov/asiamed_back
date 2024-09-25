<?php
namespace Winter\User\Models;

use Model;

class Appointment extends Model
{
    public $table = 'winter_user_appointments'; // Название таблицы в БД

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
    ];

    public $belongsTo = [
        'patient' => ['Winter\User\Models\User'],
        'doctor' => ['Winter\User\Models\User'],
    ];
}
