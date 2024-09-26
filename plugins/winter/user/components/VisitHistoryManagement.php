<?php

namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\VisitHistory;
use Winter\User\Models\User;
use Auth;
use Flash;
use ApplicationException;

class VisitHistoryManagement extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Управление историей посещений',
            'description' => 'Компонент для добавления и отображения истории посещений пациентов врачами'
        ];
    }

    public function onRun()
    {
        $this->loadVisitHistory();
    }

    protected function loadVisitHistory()
    {
        $patientId = $this->param('patient_id');
        $doctorId = Auth::getUser()->id;

        if (!$patientId) {
            throw new ApplicationException('Пациент не указан.');
        }

        // Загружаем историю посещений для текущего врача и пациента
        $this->page['visitHistory'] = VisitHistory::where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->orderBy('visit_date', 'desc')
            ->get();
    }

    public function onAddVisit()
    {
        $data = post();
        $doctorId = Auth::getUser()->id;
        $patientId = $data['patient_id'];

        if (empty($data['notes'])) {
            return [
                'error' => true,
                'message' => 'Пожалуйста, добавьте примечания о визите.'
            ];
        }

        $visit = new VisitHistory();
        $visit->doctor_id = $doctorId;
        $visit->patient_id = $patientId;
        $visit->notes = $data['notes'];
        $visit->save();

        Flash::success('История посещения добавлена.');
        return [
            'error' => false,
            'message' => 'Запись о визите успешно добавлена.'
        ];
    }
}
