<?php namespace Appointment\Visits\Models;

use Model;
use Appointment\Patients\Models\Patient;
use Winter\User\Models\User;

class Visit extends Model
{
    public $table = 'appointment_visits'; // Таблица для визитов

    // Связь визита с пациентом и врачом
    public $belongsTo = [
        'patient' => Patient::class,
        'doctor' => User::class,
    ];

    // Правила валидации для визитов (при необходимости)
    public $rules = [
        'date' => 'required',
        'diagnosis' => 'required',
    ];
}
