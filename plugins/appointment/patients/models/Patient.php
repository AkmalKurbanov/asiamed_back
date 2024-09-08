<?php namespace Appointment\Patients\Models;

use Model;
use Mail;
use Winter\User\Models\User; // Добавляем импорт класса User

use System\Models\MailTemplate;

/**
 * Model
 */
class Patient extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    public $table = 'appointment_patients_plugin';

    
    
    // public $belongsTo = [
    //     'doctor' => 'Winter\User\Models\User'
    // ];

    public $belongsTo = [
        'doctor' => User::class // Связь с моделью User
    ];

    // Метод для фильтрации по доктору
    public function scopeFilterByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
    
    /**
     * @var string The database table used by the model.
     */

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];




    

      /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($patient) {
            $patient->sendNotificationEmails();
        });
    }

    /**
     * Method to send notification emails
     */
    public function sendNotificationEmails()
    {
        Mail::send('appointment.patients::patient_notification', ['patient' => $this], function($message) {
            $message->to($this->email);
        });

        Mail::send('appointment.patients::doctor_notification', ['patient' => $this, 'doctor' => $this->doctor], function($message) {
            $message->to($this->doctor->email);
        });

    }
}
