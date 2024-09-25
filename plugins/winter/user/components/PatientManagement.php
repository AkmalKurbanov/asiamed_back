<?php

namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\User;
use Winter\User\Models\UserGroup;
use Winter\User\Models\Appointment;
use Auth;
use Mail;
use ApplicationException;
use Flash;
use Carbon\Carbon;

class PatientManagement extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Управление пациентами',
            'description' => 'Компонент для создания пациентов и прикрепления их к врачам'
        ];
    }

    // Метод для создания пациента
    public function onCreatePatient()
    {
        // Получаем данные из формы
        $data = post();

        // Валидация полей
        if (empty($data['name']) || empty($data['surname']) || empty($data['iu_telephone']) || empty($data['doctor_id']) || empty($data['appointment_date'])) {
            return [
                'error' => true,
                'message' => 'Все поля обязательны для заполнения.'
            ];
        }

        // Транслитерация имени и фамилии и удаление пробелов
        $transliteratedName = str_replace(' ', '', $this->transliterate($data['name']));
        $transliteratedSurname = str_replace(' ', '', $this->transliterate($data['surname']));

        // Ограничение длины транслитерированного имени и фамилии
        $maxLength = 64; // Максимальная длина для локальной части email
        $localPart = strtolower($transliteratedName) . '.' . strtolower($transliteratedSurname);

        // Если длина превышает допустимое значение, обрезаем её
        if (strlen($localPart) > $maxLength) {
            $localPart = substr($localPart, 0, $maxLength);
        }

        // Добавление уникального суффикса
        $uniqueSuffix = rand(1000, 9999);  // Генерация случайного числа или можно использовать time()
        $localPart .= '.' . $uniqueSuffix;

        // Генерация фиктивного, но валидного email для пациента
        $fakeEmail = $localPart . '@example.com';

        // Генерация случайного пароля (вызов существующего метода)
        $generatedPassword = $this->generateRandomPassword();

        // Создаём нового пользователя (пациента)
        $patient = new User();
        $patient->name = $data['name'];
        $patient->surname = $data['surname'];
        $patient->iu_telephone = $data['iu_telephone'];  // Поле для номера телефона
        $patient->email = $fakeEmail;  // Используем фиктивный email на основе транслитерированных данных
        $patient->password = $generatedPassword;  // Устанавливаем пароль
        $patient->password_confirmation = $generatedPassword;  // Устанавливаем подтверждение пароля
        $patient->is_activated = 1;  // Принудительная активация пользователя
        $patient->save();

        // Привязываем пользователя к группе пациентов
        $group = UserGroup::where('code', 'patients')->first();
        if ($group) {
            $patient->groups()->add($group);
        }

        // Записываем пациента к врачу
        $this->createAppointment($patient, $data['doctor_id'], $data['appointment_date']);

        // Отправляем уведомление врачу
        $doctor = User::find($data['doctor_id']);
        Mail::send('winter.user::mail.patient_attached', ['patient' => $patient, 'doctor' => $doctor], function($message) use ($doctor) {
            $message->to($doctor->email);  // Используем to() для указания получателя
            $message->subject('Новый пациент прикреплен к вам');
        });




        // Возвращаем сообщение об успехе
        return [
            'error' => false,
            'message' => 'Пациент успешно создан и прикреплен к врачу.'
        ];
    }

    // Метод транслитерации (он остаётся)
    protected function transliterate($text)
    {
        // Массив для транслитерации кириллицы в латиницу
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

        // Транслитерация строки с удалением пробелов
        return str_replace(' ', '', strtr($text, $transliteration));
    }

    // Метод для создания записи о прикреплении пациента к врачу
    protected function createAppointment($patient, $doctorId, $appointmentDate)
    {
        $appointment = new Appointment();
        $appointment->patient_id = $patient->id;
        $appointment->doctor_id = $doctorId;
        $appointment->appointment_date = Carbon::parse($appointmentDate);  // Установить дату прикрепления
        $appointment->save();
    }

    // Генерация случайного пароля для пациента
    protected function generateRandomPassword($length = 8)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    // Загрузка всех врачей для выпадающего списка
    public function loadDoctorsList()
    {
        // Загружаем всех врачей для выпадающего списка
        $doctorGroup = UserGroup::where('code', 'doctors')->first();
        return User::whereHas('groups', function ($query) use ($doctorGroup) {
            $query->where('id', $doctorGroup->id);
        })->get();
    }
}
