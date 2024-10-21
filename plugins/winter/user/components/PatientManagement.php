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
Carbon::setLocale('ru');

use Winter\User\Models\Appointment;
use Winter\User\Models\VisitHistory;
use Illuminate\Support\Facades\DB;

use Winter\User\Helpers\NotificationHelper;

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

   
    protected function loadPatients()
    {
        $currentUser = Auth::getUser();
        
        // Получаем уведомления о новых пациентах
        $newPatientNotifications = DB::table('winter_user_notifications')
            ->where('user_id', $currentUser->id)
            ->whereIn('type', ['patient_booked', 'patient_attached'])
            ->where('is_read', false)
            ->pluck('entity_id');  // IDs новых пациентов

        // Для администратора загружаем всех пациентов
        if ($currentUser->is_superuser || $currentUser->groups()->where('code', 'admins')->exists()) {
            $patients = User::with('doctor')
                ->whereHas('groups', function ($query) {
                    $query->where('code', 'patients');
                })
                ->get();
        }
        // Для врача загружаем список пациентов с будущими приемами и прикрепленных пациентов
        else if ($currentUser->groups()->where('code', 'doctors')->exists()) {
            $doctorId = $currentUser->id;

            // Пациенты, записанные на будущие приемы
            $bookedPatients = User::with(['doctor', 'appointments' => function ($query) {
                    // Показываем только будущие приемы
                    $query->where('appointment_date', '>=', Carbon::now()->startOfDay());
                }])
                ->whereHas('appointments', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })
                ->whereHas('groups', function ($query) {
                    $query->where('code', 'patients');
                })
                ->get();

            // Прикрепленные пациенты
            $attachedPatients = User::with('doctor')
                ->where('doctor_id', $doctorId)
                ->whereHas('groups', function ($query) {
                    $query->where('code', 'patients');
                })
                ->get();

            // Фильтрация новых пациентов (на основе уведомлений)
            $newBookedPatients = $bookedPatients->filter(function ($patient) use ($newPatientNotifications) {
                return $newPatientNotifications->contains($patient->id);
            })->pluck('id'); // Получаем только ID
            
            $newAttachedPatients = $attachedPatients->filter(function ($patient) use ($newPatientNotifications) {
                return $newPatientNotifications->contains($patient->id);
            })->pluck('id'); // Получаем только ID
            
            // Передаем данные в шаблон
            $this->page['bookedPatients'] = $bookedPatients;
            $this->page['attachedPatients'] = $attachedPatients;
            $this->page['newBookedPatients'] = $newBookedPatients; // Массив ID новых записанных пациентов
            $this->page['newAttachedPatients'] = $newAttachedPatients; // Массив ID новых прикрепленных пациентов
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
            $patient->iu_telephone = !empty($data['iu_telephone']) ? $data['iu_telephone'] : null;
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

            // Если выбран врач и установлен флажок "Постоянный лечащий врач"
              if (!empty($data['doctor_id']) && !empty($data['make_primary'])) {
                  $patient->doctor_id = $data['doctor_id'];
                  $patient->save();
                  
                  // Создаем уведомление о прикреплении пациента как постоянного
                  NotificationHelper::createNotification(
                      $data['doctor_id'],  // ID врача
                      $patient->id,  // ID пациента
                      'Пациент прикреплен как постоянный',  // Текст уведомления
                      'Пациенты',  // Категория уведомления
                      'patient_attached'  // Тип уведомления
                  );
              }

            // Если выбран чекбокс "с визитом"
            if (!empty($data['with_visit'])) {
                $doctorId = !empty($data['doctor_id']) ? $data['doctor_id'] : null;

                // Если выбран амбулаторный визит, создаем амбулаторную запись
                if ($data['visit_type'] === 'амбулаторный') {
                    $appointment = $this->createAppointment($patient, $doctorId, null, null, $data['visit_type']);
                } 
                // Для стационарного визита
                else if ($data['visit_type'] === 'стационарный' && $doctorId) {
                    $appointment = $this->createAppointment($patient, $doctorId, $data['appointment_date'], $data['appointment_time'], $data['visit_type']);
                }
                
            }

            // Отправляем уведомление врачу, если doctor_id указан
            if (!empty($data['doctor_id'])) {
                $doctor = User::find($data['doctor_id']);
                if ($doctor) {
                    // Отправляем email врачу
                    Mail::send('winter.user::mail.patient_attached', [
                        'patient' => $patient,
                        'doctor' => $doctor,
                        'appointment' => $appointment ?? null
                    ], function ($message) use ($doctor) {
                        $message->to($doctor->email);
                        $message->subject('Новый пациент прикреплен к вам');
                    });

                    
                    // Добавляем запись в таблицу уведомлений
                    NotificationHelper::createNotification(
                        $doctor->id,  // ID врача
                        $patient->id,  // ID пациента
                        'Новый пациент прикреплен',  // Текст уведомления
                        'Пациенты',  // Категория уведомления
                        'patient_booked'  // Тип уведомления
                    );
                    
                  }
            }

            return [
                'error' => false,
                'message' => 'Пациент успешно создан.' . (!empty($data['doctor_id']) ? ' Запись на прием успешно создана.' : '')
            ];

        } catch (\Exception $e) {
            
            return [
                'error' => true,
                'message' => 'Произошла ошибка при создании пациента. Пожалуйста, проверьте введенные данные и повторите попытку.'
            ];
        }
    }
    

    // Создание записи на приём
    protected function createAppointment($patient, $doctorId = null, $appointmentDate = null, $appointmentTime = null, $visitType)
    {
        $appointment = new Appointment();
        $appointment->visit_type = $visitType; // Устанавливаем тип визита
        $appointment->patient_id = $patient->id;
        $appointment->doctor_id = $doctorId; // Устанавливаем doctor_id, который может быть null
        $appointment->appointment_date = $appointmentDate ? Carbon::parse($appointmentDate) : null; // Может быть null для амбулаторных визитов
        $appointment->appointment_time = $appointmentTime ? Carbon::parse($appointmentTime) : null; // Может быть null для амбулаторных визитов
        
        try {
            $appointment->save(); // Попытка сохранить запись
        } catch (\Exception $e) {
           
            throw new ApplicationException('Не удалось сохранить запись приема.');
        }

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
        ->whereDate('appointment_date', '>=', Carbon::now()->startOfDay())
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
        return Carbon::parse($appointment->appointment_time)->format('H:i'); // Извлекаем только часы и минуты
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
            
            return [
                'error' => true,
                'message' => 'Доктор не выбран.'
            ];
        }

        

        // Получаем записи врача (например, из базы данных)
        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        if ($appointments->isEmpty()) {
            
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

        // Определяем, на какой странице находимся (booked или attached)
        $isAttachedPage = $this->page->fileName == 'doctor-attached-patients.htm';
        $isBookedPage = $this->page->fileName == 'doctor-booked-patients.htm';

        $patients = User::query();

        // Если пользователь — администратор
        if ($currentUser->is_superuser || $currentUser->groups()->where('code', 'admins')->exists()) {
            $patients->whereHas('groups', function ($q) {
                $q->where('code', 'patients');
            });
        }
        // Если пользователь — врач
        else if ($currentUser->groups()->where('code', 'doctors')->exists()) {
            $doctorId = $currentUser->id;

            // Логика для прикрепленных пациентов
            if ($isAttachedPage) {
                $patients->where('doctor_id', $doctorId)
                        ->whereHas('groups', function ($q) {
                            $q->where('code', 'patients');
                        });
            }
            // Логика для пациентов с будущими записями
            elseif ($isBookedPage) {
                $patients->whereHas('appointments', function ($q) use ($doctorId) {
                    $q->where('doctor_id', $doctorId)
                      ->whereDate('appointment_date', '>=', Carbon::now()->toDateString());
                });
            }
        } else {
            throw new ApplicationException('У вас нет прав для просмотра списка пациентов.');
        }

        // Поиск по запросу
        if (!empty($query)) {
            $patients->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('surname', 'like', '%' . $query . '%')
                  ->orWhere('iu_telephone', 'like', '%' . $query . '%');
            });
        }

        // Получаем отфильтрованных пациентов
        $filteredPatients = $patients->get();

        // Передаем пациентов в шаблон
        return [
            '#patient_list' => $this->renderPartial('patient_list', [
                'patients' => $filteredPatients
            ])
        ];
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


public function onMarkNotificationsAsRead()
{
    $type = post('type'); // Получаем тип уведомления из запроса
    $currentUserId = Auth::getUser()->id;

    
    // Обновляем все уведомления этого типа для текущего пользователя
    DB::table('winter_user_notifications')
        ->where('user_id', $currentUserId)
        ->where('type', $type)
        ->where('is_read', false)
        ->update(['is_read' => true, 'updated_at' => now()]);

    return ['message' => 'Уведомления помечены как прочитанные'];
}




// public function onFilterAppointmentsByDate()
// {
//     $startDate = post('start_date');
//     $endDate = post('end_date');
//     $currentUser = Auth::getUser();

//     // Если не переданы даты, возвращаем всех пациентов (сброс фильтра)
//     if (empty($startDate) || empty($endDate)) {
//         if ($currentUser->groups()->where('code', 'doctors')->exists()) {
//             $doctorId = $currentUser->id;

//             // Получаем всех пациентов врача
//             $patients = User::with('appointments')
//                 ->whereHas('appointments', function ($query) use ($doctorId) {
//                     $query->where('doctor_id', $doctorId);
//                 })
//                 ->get();
//         } elseif ($currentUser->groups()->where('code', 'admins')->exists()) {
//             // Для админа получаем всех пациентов
//             $patients = User::with('appointments')->get();
//         } else {
//             throw new ApplicationException('У вас нет прав для просмотра списка пациентов.');
//         }

//         return [
//             '#patient_list' => $this->renderPartial('patient_list', [
//                 'patients' => $patients
//             ])
//         ];
//     }

//     // Преобразуем даты к нужному формату
//     $startDate = Carbon::parse($startDate)->startOfDay();
//     $endDate = Carbon::parse($endDate)->endOfDay();

//     // Проверка прав пользователя
//     if (!$currentUser->groups()->whereIn('code', ['doctors', 'admins'])->exists()) {
//         throw new ApplicationException('У вас нет прав для просмотра списка пациентов.');
//     }

//     // Инициализация запроса для фильтрации
//     $query = User::query();

//     if ($currentUser->groups()->where('code', 'doctors')->exists()) {
//         $doctorId = $currentUser->id;

//         // Фильтрация пациентов по записям врача на указанные даты
//         $query->whereHas('appointments', function ($query) use ($doctorId, $startDate, $endDate) {
//             $query->where('doctor_id', $doctorId)
//                   ->whereBetween('appointment_date', [$startDate, $endDate]);
//         });
//     } elseif ($currentUser->groups()->where('code', 'admins')->exists()) {
//         // Для администраторов добавим условие по дате
//         $query->whereHas('appointments', function ($query) use ($startDate, $endDate) {
//             $query->whereBetween('appointment_date', [$startDate, $endDate]);
//         });
//     }

//     // Получаем отфильтрованных пациентов
//     $filteredPatients = $query->get();

//     // Проверка на наличие записей
//     if ($filteredPatients->isEmpty()) {
//         return [
//             '#patient_list' => '<tr><td colspan="6">Нет записей на выбранные даты</td></tr>'
//         ];
//     }

//     return [
//         '#patient_list' => $this->renderPartial('patient_list', [
//             'patients' => $filteredPatients
//         ])
//     ];
// }





}
