<?php

namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\User;
use Winter\User\Models\UserGroup;
use Auth;
use Mail;
use ApplicationException;
use Flash;
use Carbon\Carbon;
use Winter\User\Models\Appointment;
use Winter\User\Models\VisitHistory;

class PatientManagement extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Управление пациентами',
            'description' => 'Компонент для создания, редактирования и управления пациентами'
        ];
    }

    public function onRun()
    {
        if ($this->param('patient_id')) {
            $this->loadPatient();
        } else {
            $this->loadPatients();
        }
    }

    protected function loadPatient()
    {
        $patientId = $this->param('patient_id');

        if (!$patientId) {
            throw new ApplicationException('ID пациента не указан.');
        }

        $this->page['patient'] = User::where('id', $patientId)->whereHas('groups', function ($query) {
            $query->where('code', 'patients');
        })->first();

        if (!$this->page['patient']) {
            throw new ApplicationException('Пациент не найден.');
        }
    }

    protected function loadPatients()
    {
        $this->page['patients'] = User::whereHas('groups', function ($query) {
            $query->where('code', 'patients');
        })->get();
    }

    public function onCreatePatient()
    {
        $data = post();

        if (empty($data['name']) || empty($data['surname']) || empty($data['iu_telephone']) || empty($data['doctor_id']) || empty($data['appointment_date']) || empty($data['appointment_time'])) {
            return [
                'error' => true,
                'message' => 'Все поля обязательны для заполнения.'
            ];
        }

        $transliteratedName = str_replace(' ', '', $this->transliterate($data['name']));
        $transliteratedSurname = str_replace(' ', '', $this->transliterate($data['surname']));
        $maxLength = 64;
        $localPart = strtolower($transliteratedName) . '.' . strtolower($transliteratedSurname);

        if (strlen($localPart) > $maxLength) {
            $localPart = substr($localPart, 0, $maxLength);
        }

        $uniqueSuffix = rand(1000, 9999);
        $fakeEmail = $localPart . '.' . $uniqueSuffix . '@example.com';

        $generatedPassword = $this->generateRandomPassword();

        $patient = new User();
        $patient->name = $data['name'];
        $patient->surname = $data['surname'];
        $patient->iu_telephone = $data['iu_telephone'];
        $patient->email = $fakeEmail;
        $patient->password = $generatedPassword;
        $patient->password_confirmation = $generatedPassword;
        $patient->is_activated = 1;
        $patient->save();

        $group = UserGroup::where('code', 'patients')->first();
        if ($group) {
            $patient->groups()->add($group);
        }

        $this->createAppointment($patient, $data['doctor_id'], $data['appointment_date'], $data['appointment_time']);

        $doctor = User::find($data['doctor_id']);
        Mail::send('winter.user::mail.patient_attached', ['patient' => $patient, 'doctor' => $doctor], function ($message) use ($doctor) {
            $message->to($doctor->email);
            $message->subject('Новый пациент прикреплен к вам');
        });

        return [
            'error' => false,
            'message' => 'Пациент успешно создан и прикреплен к врачу.'
        ];
    }

    protected function transliterate($text)
    {
        $transliteration = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];

        return str_replace(' ', '', strtr($text, $transliteration));
    }

    protected function createAppointment($patient, $doctorId, $appointmentDate, $appointmentTime)
    {
        $appointment = new Appointment();
        $appointment->patient_id = $patient->id;
        $appointment->doctor_id = $doctorId;
        $appointment->appointment_date = Carbon::parse($appointmentDate); // Установить дату прикрепления
        $appointment->appointment_time = Carbon::parse($appointmentTime); // Установить время прикрепления
        $appointment->save();
    }

    protected function generateRandomPassword($length = 8)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    public function loadDoctorsList()
    {
        $doctorGroup = UserGroup::where('code', 'doctors')->first();
        return User::whereHas('groups', function ($query) use ($doctorGroup) {
            $query->where('id', $doctorGroup->id);
        })
        ->where('is_active', true) // Фильтруем только активных врачей
        ->get();
    }

    public function onUpdatePatient()
    {
        $data = post();

        if (empty($data['patient_id'])) {
            return [
                'error' => true,
                'message' => 'ID пациента не указан.'
            ];
        }

        $patient = User::find($data['patient_id']);
        if (!$patient || !$patient->groups()->where('code', 'patients')->exists()) {
            return [
                'error' => true,
                'message' => 'Пациент не найден.'
            ];
        }

        $patient->name = $data['name'] ?? $patient->name;
        $patient->surname = $data['surname'] ?? $patient->surname;
        $patient->iu_telephone = $data['iu_telephone'] ?? $patient->iu_telephone;
        $patient->save();

        return [
            'error' => false,
            'message' => 'Информация о пациенте успешно обновлена.'
        ];
    }

    public function onGetBookedTimes()
{
    $doctorId = post('doctor_id');
    $selectedDate = post('selected_date');

    if (!$selectedDate) {
        return [
            'error' => true,
            'message' => 'Дата не выбрана.'
        ];
    }

    // Устанавливаем локализацию Carbon на русский
    Carbon::setLocale('ru');

    $startOfDay = Carbon::parse($selectedDate)->startOfDay();
    $endOfDay = Carbon::parse($selectedDate)->endOfDay();

    // Получаем записи на дату
    $appointments = Appointment::where('doctor_id', $doctorId)
        ->whereBetween('appointment_date', [$startOfDay, $endOfDay])
        ->get();

    // Проверяем полученные данные
    if ($appointments->isEmpty()) {
        return [
            'error' => true,
            'message' => 'Записей не найдено.'
        ];
    }

    return [
        'times' => $appointments->map(function ($appointment) {
            return [
                'day_of_week' => Carbon::parse($appointment->appointment_date)->isoFormat('dddd'),
                'date' => Carbon::parse($appointment->appointment_date)->format('d.m.Y'),
                'time' => $appointment->appointment_time // Добавляем поле времени
            ];
        })
    ];
}

public function onSearchPatients()
{
    $query = post('search_query');

    if (empty($query)) {
        $this->page['patients'] = User::whereHas('groups', function ($q) {
            $q->where('code', 'patients');
        })->get();
    } else {
        $this->page['patients'] = User::whereHas('groups', function ($q) {
            $q->where('code', 'patients');
        })
        ->where(function ($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
              ->orWhere('surname', 'like', '%' . $query . '%')
              ->orWhere('iu_telephone', 'like', '%' . $query . '%');
        })
        ->get();
    }

    // Обновляем partial с пациентами
    return ['#patient_list' => $this->renderPartial('patient_list', ['patients' => $this->page['patients']])];
}







  public function onCreateVisitHistory()
{
    $data = post();

    // Получаем ID текущего авторизованного врача
    $doctorId = Auth::getUser()->id;

    // Проверка обязательных полей
    if (empty($data['patient_id']) || empty($data['visit_date']) || empty($data['notes'])) {
        throw new ApplicationException('Все поля обязательны для заполнения.');
    }

    // Создание новой записи в истории посещений
    $visitHistory = new VisitHistory();
    $visitHistory->patient_id = $data['patient_id'];
    $visitHistory->doctor_id = $doctorId;
    $visitHistory->visit_date = Carbon::parse($data['visit_date']);
    $visitHistory->notes = $data['notes'];
    $visitHistory->status = $data['status'] ?? 'Ожидается'; // Если статус не указан, по умолчанию "Ожидается"
    $visitHistory->save();

    return [
        'error' => false,
        'message' => 'Запись успешно добавлена.'
    ];
}




  public function loadVisitHistory($patientId)
  {
      $doctorId = Auth::getUser()->id; // Получаем ID текущего врача

      // Загружаем записи посещений для данного врача и пациента
      return VisitHistory::where('patient_id', $patientId)
                        ->where('doctor_id', $doctorId)
                        ->orderBy('visit_date', 'desc')
                        ->get();
  }


  public function onFilterVisitHistory()
  {
      $data = post();

      $query = VisitHistory::where('patient_id', $data['patient_id']);

      if (!empty($data['doctor_id'])) {
          $query->where('doctor_id', $data['doctor_id']);
      }

      if (!empty($data['start_date'])) {
          $query->where('visit_date', '>=', Carbon::parse($data['start_date']));
      }

      if (!empty($data['end_date'])) {
          $query->where('visit_date', '<=', Carbon::parse($data['end_date']));
      }

      $this->page['visit_histories'] = $query->orderBy('visit_date', 'desc')->get();

      // Обновляем partial с результатами
      return ['#visit_history_list' => $this->renderPartial('visit_history_list', ['visit_histories' => $this->page['visit_histories']])];
  }


}