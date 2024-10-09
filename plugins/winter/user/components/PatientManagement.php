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

        // Для администратора загружаем всех пациентов
        if ($currentUser->is_superuser || $currentUser->groups()->where('code', 'admins')->exists()) {
            $this->page['patients'] = User::with('doctor') // Подгружаем данные о врачах
                ->whereHas('groups', function ($query) {
                    $query->where('code', 'patients');
                })->get();
        } 
        // Для врача загружаем два списка:
        else if ($currentUser->groups()->where('code', 'doctors')->exists()) {
            $doctorId = $currentUser->id;

            // Текущая дата и время
            $now = Carbon::now();
            $currentDate = $now->toDateString();  // Только дата
            $currentTime = $now->toTimeString();  // Только время

            // 1. Пациенты, записанные на будущие приемы
            $bookedPatients = User::with('doctor') // Подгружаем данные о врачах
                ->whereHas('appointments', function ($query) use ($doctorId, $currentDate, $currentTime) {
                    $query->where('doctor_id', $doctorId)
                          ->where(function ($q) use ($currentDate, $currentTime) {
                              // Фильтруем по будущей дате
                              $q->where('appointment_date', '>', $currentDate)
                                // Или по текущей дате и будущему времени
                                ->orWhere(function ($q2) use ($currentDate, $currentTime) {
                                    $q2->where('appointment_date', '=', $currentDate)
                                      ->where('appointment_time', '>', $currentTime);
                                });
                          });
                })
                ->whereHas('groups', function ($query) {
                    $query->where('code', 'patients');
                })->get();

            // 2. Прикрепленные пациенты по полю doctor_id
            $attachedPatients = User::with('doctor') // Подгружаем данные о врачах
                ->where('doctor_id', $doctorId)
                ->whereHas('groups', function ($query) {
                    $query->where('code', 'patients');
                })->get();

            // Передаем данные в шаблон
            $this->page['bookedPatients'] = $bookedPatients;
            $this->page['attachedPatients'] = $attachedPatients;

            // Передаем количество в шаблон
            $this->page['bookedPatientsCount'] = $bookedPatients->count();
            $this->page['attachedPatientsCount'] = $attachedPatients->count();
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
            if (empty($data['name'])) {
                throw new ApplicationException('Пожалуйста, введите имя.');
            }

            if (empty($data['surname'])) {
                throw new ApplicationException('Пожалуйста, введите фамилию.');
            }

            if (empty($data['iu_telephone'])) {
                throw new ApplicationException('Пожалуйста, введите номер телефона.');
            }

            // Проверка поля doctor_id, чтобы исключить значение по умолчанию "Выберите врача"
            if (!empty($data['doctor_id']) && $data['doctor_id'] === 'Выберите врача') {
                throw new ApplicationException('Пожалуйста, выберите врача из списка.');
            }

            // Проверка наличия даты и времени, если указан doctor_id
            if (!empty($data['doctor_id'])) {
                if (empty($data['appointment_date'])) {
                    throw new ApplicationException('Пожалуйста, выберите дату приема.');
                }

                if (empty($data['appointment_time'])) {
                    throw new ApplicationException('Пожалуйста, выберите время приема.');
                }
            }

            // Транслитерация имени и фамилии для создания уникального email
            $transliteratedName = str_replace(' ', '', $this->transliterate($data['name']));
            $transliteratedSurname = str_replace(' ', '', $this->transliterate($data['surname']));
            $localPart = strtolower($transliteratedName) . '.' . strtolower($transliteratedSurname);

            // Ограничиваем длину email и генерируем случайный суффикс
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

            // Сначала сохраняем пациента
            $patient->save();

            // Привязка пациента к группе "patients" после сохранения
            $group = UserGroup::where('code', 'patients')->first();
            if ($group) {
                $patient->groups()->add($group);
            }

            // Проверяем, установлен ли чекбокс "Сделать основным врачом"
            if (!empty($data['doctor_id']) && !empty($data['make_primary'])) {
                $patient->doctor_id = $data['doctor_id'];
                $patient->save();
            }

            // Запись на прием к врачу, если doctor_id указан
            if (!empty($data['doctor_id'])) {
                $appointment = $this->createAppointment($patient, $data['doctor_id'], $data['appointment_date'], $data['appointment_time']);
                
                // Отправляем уведомление врачу
                $doctor = User::find($data['doctor_id']);
                Mail::send('winter.user::mail.patient_attached', [
                    'patient' => $patient,
                    'doctor' => $doctor,
                    'appointment' => $appointment
                ], function ($message) use ($doctor) {
                    $message->to($doctor->email);
                    $message->subject('Новый пациент прикреплен к вам');
                });
            }

            return [
                'error' => false,
                'message' => 'Пациент успешно создан и прикреплен к врачу.'
            ];

        } catch (\Exception $e) {
            // Обработка ошибки
            return [
                'error' => true,
                'message' => $e instanceof ApplicationException ? $e->getMessage() : 'Произошла ошибка при создании пациента. Пожалуйста, проверьте введенные данные и повторите попытку.'
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

    return $appointment; // Возвращаем объект Appointment
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

          // Обновление данных пациента (админ может редактировать)
          $patient->name = $data['name'] ?? $patient->name;
          $patient->surname = $data['surname'] ?? $patient->surname;
          $patient->iu_telephone = $data['iu_telephone'] ?? $patient->iu_telephone;
          $patient->address = $data['address'] ?? $patient->address; 
          $patient->gender = $data['gender'] ?? $patient->gender; 

          // Преобразование даты рождения в формат YYYY-MM-DD
          if (!empty($data['birthdate'])) {
              $birthdate = \DateTime::createFromFormat('d.m.Y', $data['birthdate']);
              if ($birthdate) {
                  $patient->birthdate = $birthdate->format('Y-m-d'); // Сохраняем в формате YYYY-MM-DD
              } else {
                  throw new ApplicationException('Неверный формат даты рождения.');
              }
          }

          // Проверка doctor_id, если админ выбирает врача
          if (!empty($data['doctor_id'])) {
              $doctor = User::find($data['doctor_id']);
              if (!$doctor || !$doctor->groups()->where('code', 'doctors')->exists()) {
                  throw new ApplicationException('Доктор не найден или не имеет нужных прав.');
              }

              // Обновляем doctor_id
              if (!empty($data['make_primary'])) {
                  $patient->doctor_id = $data['doctor_id'];
              }
          } else {
              // Если doctor_id не передан и чекбокс не отмечен, оставляем doctor_id пустым
              if (!empty($data['make_primary'])) {
                  $patient->doctor_id = null;
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







//     // Обновление данных пациента врачом
//     public function onUpdatePatientByDoctor()
// {
//     try {
//         $data = post();

//         // Проверка наличия ID пациента
//         if (empty($data['patient_id'])) {
//             throw new ApplicationException('ID пациента не указан.');
//         }

//         // Поиск пациента
//         $patient = User::find($data['patient_id']);
//         if (!$patient || !$patient->groups()->where('code', 'patients')->exists()) {
//             throw new ApplicationException('Пациент не найден.');
//         }

//         // Проверка прав текущего пользователя
//         $currentUser = Auth::getUser();
//         if (!$currentUser->groups()->where('code', 'doctors')->exists()) {
//             throw new ApplicationException('У вас нет прав для редактирования данных пациента.');
//         }

//         // Врач может редактировать следующие поля пациента
//         $patient->gender = $data['gender'] ?? $patient->gender;
//         $patient->address = $data['address'] ?? $patient->address;

//         // Проверка даты рождения и форматирование
//         if (!empty($data['birthdate'])) {
//             $birthdate = \DateTime::createFromFormat('d.m.Y', $data['birthdate']);
//             if ($birthdate) {
//                 $patient->birthdate = $birthdate->format('Y-m-d'); // Сохраняем в формате YYYY-MM-DD
//             } else {
//                 throw new ApplicationException('Неверный формат даты рождения.');
//             }
//         }

//         // Обновление остальных полей
//         $patient->name = $data['name'] ?? $patient->name;
//         $patient->surname = $data['surname'] ?? $patient->surname;
//         $patient->iu_telephone = $data['iu_telephone'] ?? $patient->iu_telephone;

//         // Сохранение изменений
//         $patient->save();

//         // Возвращаем сообщение об успешном обновлении
//         Flash::success('Данные пациента успешно обновлены врачом.');

//         return [
//             'error' => false,
//             'message' => 'Данные пациента успешно обновлены.',
//             'patient' => $patient
//         ];
//     } catch (\Exception $e) {
//         return [
//             'error' => true,
//             'message' => 'Ошибка при обновлении данных пациента: ' . $e->getMessage()
//         ];
//     }
// }


    

    public function onGetBookedTimes()
    {
        $doctorId = post('doctor_id');
        $selectedDate = post('selected_date');

        if (!$doctorId || !$selectedDate) {
            return [
                'error' => true,
                'message' => 'Доктор и дата должны быть выбраны.'
            ];
        }

        // Получаем записи для выбранного доктора и даты
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>=', Carbon::now()->startOfDay()) // Только будущие даты
            ->whereDate('appointment_date', '=', Carbon::parse($selectedDate)->toDateString())
            ->get();

        if ($appointments->isEmpty()) {
            return [
                'error' => false,
                'times' => [],
                'message' => 'Свободное время доступно на весь день.'
            ];
        }

        $bookedTimes = $appointments->map(function ($appointment) {
        if (is_string($appointment->appointment_time)) {
            // Преобразуем в Carbon, если это строка
            $appointmentTime = Carbon::parse($appointment->appointment_time);
        } else {
            // Если это уже объект времени, просто используем его
            $appointmentTime = $appointment->appointment_time;
        }
        return $appointmentTime->format('H:i');
        });

        return [
            'error' => false,
            'times' => $bookedTimes
        ];
    }

    public function onGetDoctorSchedule()
    {
        $doctorId = post('doctor_id');

        if (!$doctorId) {
            \Log::error('Не указан ID врача');
            return [
                'error' => true,
                'message' => 'Доктор не выбран.'
            ];
        }

        \Log::info('Загружаем расписание для врача с ID: ' . $doctorId);

        // Получаем записи врача (например, из базы данных)
        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        if ($appointments->isEmpty()) {
            \Log::info('Нет записей для врача с ID: ' . $doctorId);
            return [
                'error' => false,
                'message' => 'Нет записей для выбранного врача.',
                'times' => []
            ];
        }

        // Преобразуем данные для календаря
        $times = [];
        foreach ($appointments as $appointment) {
            $times[] = [
                'title' => 'Запись', // Название события
                'start' => $appointment->appointment_date . 'T' . $appointment->appointment_time, // Дата и время начала
                'end'   => $appointment->appointment_date . 'T' . $appointment->appointment_time // Дата и время окончания
            ];
        }

        \Log::info('Записи для врача с ID: ' . $doctorId, $times);

        return [
            'error' => false,
            'times' => $times
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
