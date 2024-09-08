<?php namespace Appointment\Patients\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Appointment\Patients\Models\Patient;

use Log;
class Patients extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController',        'Backend\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Appointment.Patients', 'Patients');
    }

    


public function onFilterByDoctor()
{
    $doctorId = post('Patient[doctor]');
    $appointmentTimes = [];

    if ($doctorId) {
        $appointmentTimes = Patient::where('doctor_id', $doctorId)
            ->orderBy('appointment_date', 'asc')
            ->pluck('appointment_date');
    }

    // Генерируем HTML-код для отображения списка времени записей
    $html = '<ul>';
    foreach ($appointmentTimes as $time) {
        $html .= '<li>' . \Carbon\Carbon::parse($time)->format('d.m.Y H:i') . '</li>';
    }
    if (empty($appointmentTimes)) {
        $html .= '<li>Нет записей.</li>';
    }
    $html .= '</ul>';

    // Возвращаем HTML-код
    return ['#appointmentTimesList' => $html];
}






    

}
