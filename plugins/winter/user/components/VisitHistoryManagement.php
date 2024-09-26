<?php namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\VisitHistory;
use Winter\User\Models\User;
use Auth;
use ApplicationException;

class VisitHistoryManagement extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Управление историей посещений',
            'description' => 'Компонент для добавления и просмотра истории посещений пациентов'
        ];
    }

    public function onRun()
    {
        // Загружаем пациента для страницы
        $patientId = $this->param('patient_id');
        $this->page['patient'] = User::find($patientId);
        $this->page['visitHistory'] = $this->loadVisitHistory($patientId);
    }

    public function onAddVisitHistory()
    {
        $data = post();

        // Получаем ID врача
        $doctor = Auth::getUser();

        if (!$doctor->groups()->where('code', 'doctors')->exists()) {
            throw new ApplicationException('У вас нет прав на добавление записей.');
        }

        // Валидация полей
        if (empty($data['visit_notes']) || empty($data['visit_date']) || empty($data['patient_id'])) {
            throw new ApplicationException('Все поля обязательны для заполнения.');
        }

        // Создаём запись в истории посещений
        VisitHistory::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctor->id,
            'visit_notes' => $data['visit_notes'],
            'visit_date' => $data['visit_date'],
        ]);

        Flash::success('Запись успешно добавлена в историю посещений.');
    }

    // Метод для загрузки истории посещений для конкретного пациента
    public function loadVisitHistory($patientId)
    {
        return VisitHistory::where('patient_id', $patientId)->get();
    }
}
