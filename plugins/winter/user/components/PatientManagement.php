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
            'description' => 'Компонент для создания, редактирования и управления пациентами',
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

    // Загрузка пациента по ID
    protected function loadPatient()
    {
        $patientId = $this->param('patient_id');

        if (!$patientId) {
            throw new ApplicationException('ID пациента не указан.');
        }

        $this->page['patient'] = User::with('doctor') // Загружаем данные о враче
    ->where('id', $patientId)
    ->whereHas('groups', function ($query) {
        $query->where('code', 'patients');
    })
    ->first();

        if (!$this->page['patient']) {
            throw new ApplicationException('Пациент не найден.');
        }
    }

    // Загрузка пациентов с учётом роли (админ или врач)
    protected function loadPatients()
    {
        $currentUser = Auth::getUser();

        // Для администратора
        if ($currentUser->is_superuser || $currentUser->groups()->where('code', 'admins')->exists()) {
            $this->page['patients'] = User::whereHas('groups', function ($query) {
                $query->where('code', 'patients');
            })->get();
        } 
        // Для врача
        else if ($currentUser->groups()->where('code', 'doctors')->exists()) {
            $doctorId = $currentUser->id;
            $this->page['patients'] = User::whereHas('appointments', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })
            ->whereHas('groups', function ($query) {
                $query->where('code', 'patients');
            })->get();
        } 
        // Ошибка, если нет прав
        else {
            throw new ApplicationException('У вас нет прав для просмотра списка пациентов.');
        }
    }

    // Создание пациента (doctor_id — необязательное поле)
    public function onCreatePatient()
    {
      try {
        $data = post();

        // Проверка обязательных полей
        if (empty($data['name']) || empty($data['surname']) || empty($data['iu_telephone'])) {
            throw new ApplicationException('Имя, фамилия и телефон обязательны для заполнения.');
            return [
            'error' => true,
            'message' => 'Имя, фамилия и телефон обязательны для заполнения.'
        ];
        }

        // Транслитерация имени и фамилии для создания уникального email
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

        // Создание пациента
        $patient = new User();
        $patient->name = $data['name'];
        $patient->surname = $data['surname'];
        $patient->iu_telephone = $data['iu_telephone'];
        $patient->email = $fakeEmail;
        $patient->password = $generatedPassword;
        $patient->password_confirmation = $generatedPassword;
        $patient->is_activated = 1;

        // Проверяем, установлен ли чекбокс "Сделать основным врачом"
          if (!empty($data['doctor_id']) && !empty($data['make_primary'])) {
              $patient->doctor_id = $data['doctor_id']; // Устанавливаем doctor_id только если чекбокс "Сделать основным" установлен
          }

        $patient->save();

        // Привязка пациента к группе "patients"
        $group = UserGroup::where('code', 'patients')->first();
        if ($group) {
            $patient->groups()->add($group);
        }

          // Запись на прием к врачу, если doctor_id указан
          if (!empty($data['doctor_id'])) {
              $this->createAppointment($patient, $data['doctor_id'], $data['appointment_date'], $data['appointment_time']);

              // Отправка уведомления на почту врачу
              $doctor = User::find($data['doctor_id']);
              Mail::send('winter.user::mail.patient_attached', ['patient' => $patient, 'doctor' => $doctor], function ($message) use ($doctor) {
                  $message->to($doctor->email);
                  $message->subject('Новый пациент прикреплен к вам');
              });
          }

        // Возвращаем успешное сообщение в виде JSON
        return [
            'error' => false,
            'message' => 'Пациент успешно создан и прикреплен к врачу.'
        ];
      }catch (\Exception $e) {
        // Возвращаем ошибку в формате JSON
        return [
            'error' => true,
            'message' => 'Ошибка при создании пациента: ' . $e->getMessage()
        ];
    }
    }

    // Создание записи на приём
    protected function createAppointment($patient, $doctorId, $appointmentDate, $appointmentTime)
    {
        $appointment = new Appointment();
        $appointment->patient_id = $patient->id;
        $appointment->doctor_id = $doctorId;
        $appointment->appointment_date = Carbon::parse($appointmentDate);
        $appointment->appointment_time = Carbon::parse($appointmentTime);
        $appointment->save();
    }



  public function onUpdatePatient()
{
    try {
        $data = post();

        // Проверка наличия ID пациента
        if (empty($data['patient_id'])) {
            throw new ApplicationException('ID пациента не указан.');
        }

        // Поиск пациента
        $patient = User::find($data['patient_id']);
        if (!$patient || !$patient->groups()->where('code', 'patients')->exists()) {
            throw new ApplicationException('Пациент не найден.');
        }

        // Проверка на права администратора или врача
        $currentUser = Auth::getUser();
        if (!$currentUser->is_superuser && !$currentUser->groups()->where('code', 'admins')->exists() && !$currentUser->groups()->where('code', 'doctors')->exists()) {
            throw new ApplicationException('У вас нет прав для редактирования данных пациента.');
        }

        // Обновление данных пациента
        $patient->name = $data['name'] ?? $patient->name;
        $patient->surname = $data['surname'] ?? $patient->surname;
        $patient->iu_telephone = $data['iu_telephone'] ?? $patient->iu_telephone;

        // Проверяем, если установлен чекбокс "Сделать основным врачом"
        if (!empty($data['doctor_id'])) {
            $doctor = User::find($data['doctor_id']);
            if (!$doctor || !$doctor->groups()->where('code', 'doctors')->exists()) {
                throw new ApplicationException('Доктор не найден или не имеет нужных прав.');
            }

            // Если чекбокс "Сделать основным врачом" отмечен, обновляем doctor_id
            if (!empty($data['make_primary'])) {
                $patient->doctor_id = $data['doctor_id'];
            }
        }

        // Сохранение изменений
        $patient->save();

        // Загружаем данные о враче, если назначен
        $doctor = null;
        if ($patient->doctor_id) {
            $doctor = User::find($patient->doctor_id);
        }

        return [
            'error' => false,
            'message' => 'Данные пациента успешно обновлены.',
            'patient' => $patient,
            'doctor' => $doctor // Возвращаем данные о враче, если он есть
        ];
    } catch (\Exception $e) {
        // Возвращаем ошибку в формате JSON
        return [
            'error' => true,
            'message' => 'Ошибка при обновлении данных пациента: ' . $e->getMessage()
        ];
    }
}



public function onDetachDoctor()
{
    try {
        $patientId = post('patient_id');

        $patient = User::find($patientId);
        if (!$patient) {
            throw new ApplicationException('Пациент не найден.');
        }

        // Открепляем врача
        $patient->doctor_id = null;
        $patient->save();

        return [
            'error' => false,
            'message' => 'Врач успешно откреплен.',
            'doctor' => null, // Врач теперь откреплен
            'patient' => $patient
        ];
    } catch (\Exception $e) {
       return [
    'error' => false,
    'message' => 'Врач успешно откреплен.',
    'doctor' => null, // Врач теперь откреплен
    'patient' => $patient->toArray() // Конвертируем объект пациента в массив
];
    }
}







    // Обновление данных пациента врачом
    public function onUpdatePatientByDoctor()
    {
        $data = post();

        if (empty($data['patient_id'])) {
            throw new ApplicationException('ID пациента не указан.');
        }

        $patient = User::find($data['patient_id']);
        if (!$patient || !$patient->groups()->where('code', 'patients')->exists()) {
            throw new ApplicationException('Пациент не найден.');
        }

        $currentUser = Auth::getUser();
        if (!$currentUser->groups()->where('code', 'doctors')->exists()) {
            throw new ApplicationException('У вас нет прав для редактирования данных пациента.');
        }

        // Врач может редактировать все поля пациента
        $patient->gender = $data['gender'] ?? $patient->gender;
        $patient->address = $data['address'] ?? $patient->address;
        $patient->birthdate = !empty($data['birthdate']) ? Carbon::parse($data['birthdate'])->format('Y-m-d') : null;
        $patient->name = $data['name'] ?? $patient->name;
        $patient->surname = $data['surname'] ?? $patient->surname;
        $patient->iu_telephone = $data['iu_telephone'] ?? $patient->iu_telephone;

        $patient->save();

        Flash::success('Данные пациента успешно обновлены врачом.');
    }

    

    public function onGetBookedTimes()
    {
        $doctorId = post('doctor_id');
        $selectedDate = post('selected_date');

        if (!$selectedDate || !$doctorId) {
            return [
                'error' => true,
                'message' => 'Доктор и дата должны быть выбраны.'
            ];
        }
        
        Carbon::setLocale('ru');
        // Устанавливаем начало и конец дня для выбранной даты
        $startOfDay = Carbon::parse($selectedDate)->startOfDay();
        $endOfDay = Carbon::parse($selectedDate)->endOfDay();

        // Получаем все записи на выбранную дату
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('appointment_date', [$startOfDay, $endOfDay])
            ->get();

        if ($appointments->isEmpty()) {
            return [
                'times' => [],
                'message' => 'Свободное время доступно на весь день.'
            ];
        }

        // Возвращаем список занятых временных слотов
        $bookedTimes = $appointments->map(function ($appointment) {
            return $appointment->appointment_time->format('H:i');
        });

        return [
            'times' => $bookedTimes
        ];
    }



    // Поиск пациентов для врача и админа
    public function onSearchPatients()
    {
        $query = post('search_query');
        $currentUser = Auth::getUser();

        $patients = User::query();

        if ($currentUser->is_superuser || $currentUser->groups()->where('code', 'admins')->exists()) {
            $patients->whereHas('groups', function ($q) {
                $q->where('code', 'patients');
            });
        } else if ($currentUser->groups()->where('code', 'doctors')->exists()) {
            $doctorId = $currentUser->id;
            $patients->whereHas('appointments', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            });
        } else {
            throw new ApplicationException('У вас нет прав для просмотра списка пациентов.');
        }

        if (!empty($query)) {
            $patients->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('surname', 'like', '%' . $query . '%')
                  ->orWhere('iu_telephone', 'like', '%' . $query . '%');
            });
        }

        $this->page['patients'] = $patients->get();

        return ['#patient_list' => $this->renderPartial('patient_list', ['patients' => $this->page['patients']])];
    }

    // Создание визита и запись в историю
    public function onCreateVisitHistory()
    {
        $data = post();
        $doctorId = Auth::getUser()->id;

        if (empty($data['patient_id']) || empty($data['visit_date']) || empty($data['notes'])) {
            throw new ApplicationException('Все поля обязательны для заполнения.');
        }

        $visitDate = Carbon::createFromFormat('d.m.Y', $data['visit_date'])->format('Y-m-d');

        $visitHistory = new VisitHistory();
        $visitHistory->patient_id = $data['patient_id'];
        $visitHistory->doctor_id = $doctorId;
        $visitHistory->visit_date = $visitDate;
        $visitHistory->notes = $data['notes'];
        $visitHistory->status = $data['status'] ?? 'Ожидается';
        $visitHistory->save();

        Flash::success('Запись успешно добавлена.');
    }

    // Функция транслитерации для преобразования кириллических символов
    protected function transliterate($text)
    {
        $transliteration = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '',
            'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E', 
            'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 
            'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 
            'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 
            'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];

        return strtr($text, $transliteration);
    }

    // Генерация случайного пароля
    protected function generateRandomPassword($length = 8)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }


    public function loadDoctorsList()
{
    // Загружаем врачей только из группы 'doctors' и только активных
    $doctorGroup = UserGroup::where('code', 'doctors')->first();

    if ($doctorGroup) {
        return User::whereHas('groups', function ($query) use ($doctorGroup) {
            $query->where('id', $doctorGroup->id);
        })
        ->where('is_activated', 1) // Фильтруем только активных врачей
        ->get();
    }

    return [];
}

}
