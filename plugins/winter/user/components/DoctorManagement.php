<?php
namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\User;
use Winter\User\Models\UserGroup;
use Auth;
use ApplicationException;
use Flash;
use Carbon\Carbon;
Carbon::setLocale('ru');

use Illuminate\Support\Facades\DB;

class DoctorManagement extends ComponentBase
{
    

    public function componentDetails()
    {
        return [
            'name' => 'Управление врачами',
            'description' => 'Компонент для создания, редактирования и отображения списка врачей'
        ];
    }

    // Метод для отображения списка врачей
    public function onRun()
    {
         // Устанавливаем локализацию Carbon на русский
        Carbon::setLocale('ru');  
        
        // Проверка, если это страница редактирования врача
        if ($this->param('id')) {
            $this->loadDoctorData();  // Загружаем данные врача для редактирования
        } else {
            $this->loadDoctorsList();  // Загружаем список всех врачей
        }
        
        
      


    }

    // Загружаем список врачей
    protected function loadDoctorsList()
    {
        // Загружаем группу врачей
        $doctorGroup = UserGroup::where('code', 'doctors')->first();

        // Проверяем, что группа найдена
        if (!$doctorGroup) {
            throw new ApplicationException('Группа "doctors" не найдена.');
        }

        // Загружаем всех пользователей, принадлежащих к группе врачей
        $this->page['doctors'] = User::whereHas('groups', function ($query) use ($doctorGroup) {
            $query->where('id', $doctorGroup->id);
        })->get();
    }

    // Метод для создания нового врача
    public function onCreateDoctor()
{
    // Проверка прав администратора
    if (!Auth::getUser()->groups()->where('code', 'admins')->exists()) {
        throw new ApplicationException('У вас нет прав для создания врача.');
    }

    // Получаем данные из формы
    $data = post();

    // Валидация полей
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return [
            'error' => true,
            'message' => 'Все поля обязательны для заполнения.'
        ];
    }

    // Создаём нового пользователя
    $user = new User();
    $user->name = $data['name'];
    $user->surname = $data['surname'];
    $user->email = $data['email'];
    $user->iu_telephone = $data['iu_telephone'];
    $user->iu_job = $data['iu_job'];
    $user->password = $data['password'];
    $user->password_confirmation = $data['password_confirmation'];
    $user->is_activated = 1;  // Принудительная активация пользователя
    $user->save();

    // Привязываем пользователя к группе врачей
    $group = UserGroup::where('code', 'doctors')->first();
    if ($group) {
        $user->groups()->add($group);
    }

    // Возвращаем сообщение об успехе
    return [
        'error' => false,
        'message' => 'Врач успешно создан и активирован.'
    ];
}

    // Метод для загрузки данных врача на страницу редактирования
    protected function loadDoctorData()
    {
       
    
        // Получаем ID врача из URL
        $doctorId = $this->param('id');

        // Загружаем данные врача
        $doctor = User::find($doctorId);

        // Проверяем, что врач существует и принадлежит к группе врачей
        if (!$doctor) {
            throw new ApplicationException('Врач с ID ' . $doctorId . ' не найден.');
        }

        if (!$doctor->groups()->where('code', 'doctors')->exists()) {
            throw new ApplicationException('Этот пользователь не является врачом.');
        }

        // Логируем данные врача для отладки
        trace_log('Данные врача: ' . print_r($doctor->toArray(), true));

        // Передаем данные врача на страницу
        $this->page['doctorData'] = $doctor;
    }

    // Метод для обновления данных врача
    public function onUpdateDoctor()
    {
        // Проверка прав администратора
        if (!Auth::getUser()->groups()->where('code', 'admins')->exists()) {
            throw new ApplicationException('У вас нет прав для редактирования врача.');
        }

        // Получаем данные из формы
        $data = post();

        // Ищем врача по ID
        $doctor = User::find($data['doctor_id']);
        if (!$doctor || !$doctor->groups()->where('code', 'doctors')->exists()) {
            throw new ApplicationException('Врач не найден или не является врачом.');
        }

        // Обновляем данные врача
        $doctor->name = $data['name'];
        $doctor->surname = $data['surname'];
        $doctor->email = $data['email'];
        $doctor->iu_telephone = $data['iu_telephone'];
        $doctor->iu_job = $data['iu_job'];
        $doctor->is_active = isset($data['is_active']) ? true : false;

        // Если пароль изменён, обновляем его
        if (!empty($data['password'])) {
            if ($data['password'] !== $data['password_confirmation']) {
                throw new ApplicationException('Пароль и подтверждение пароля не совпадают.');
            }
            $doctor->password = $data['password'];
            $doctor->password_confirmation = $data['password_confirmation'];
        }

        // Сохраняем изменения
        $doctor->save();

        // Возвращаем сообщение об успешном обновлении
        return [
            'error' => false,
            'message' => 'Данные врача успешно обновлены.'
        ];
    }
   
 public function onSearchDoctors()
{
    $query = post('search_query'); // Получаем поисковый запрос

    // Если запрос пустой, загружаем всех врачей
    if (empty($query)) {
        $this->page['doctors'] = User::whereHas('groups', function ($q) {
            $q->where('code', 'doctors');
        })->get();
    } else {
        // Поиск врачей по имени, фамилии или специальности
        $this->page['doctors'] = User::whereHas('groups', function ($q) {
            $q->where('code', 'doctors');
        })
        ->where(function ($q) use ($query) {
            $q
                ->where('name', 'like', '%' . $query . '%')
                ->orWhere('surname', 'like', '%' . $query . '%')
                ->orWhere('iu_job', 'like', '%' . $query . '%')
                ->orWhere('email', 'like', '%' . $query . '%'); 
        })
        ->get();
    }

    // Обновляем partial с врачами
    return ['#doctor_list' => $this->renderPartial('doctor_list', ['doctors' => $this->page['doctors']])];
}


protected function getNotificationText($type)
{
    $notificationTypes = [
        'patient_attached' => 'Новый пациент',
        'appointment_scheduled' => 'Запись на приём',
        'message_received' => 'Новое сообщение',
        // Добавьте другие типы уведомлений
    ];

    return $notificationTypes[$type] ?? 'Общее уведомление';
}

// Получение списка уведомлений
public function onGetNotifications()
{
    $userId = Auth::getUser()->id;

    // Получаем уведомления пользователя
    $notifications = DB::table('winter_user_notifications')
        ->where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

    // Если уведомлений нет, возвращаем пустой массив
    if ($notifications->isEmpty()) {
        return [
            'notifications' => []
        ];
    }

    // Форматируем уведомления
    $formattedNotifications = $notifications->map(function($notification) {
        return [
        'id' => $notification->id,
        'text' => $this->getNotificationText($notification->type), // Преобразуем тип в понятный текст
        'category' => !empty($notification->category) ? $notification->category : 'Общие уведомления',
        'time' => Carbon::parse($notification->created_at)->diffForHumans(),
        'url' => url('/edit-patient/' . $notification->entity_id),
    ];
    });

    return [
        'notifications' => $formattedNotifications
    ];
}


// Получение количества непрочитанных уведомлений
public function onGetUnreadCount()
{
    $userId = Auth::getUser()->id;

    $unreadCount = DB::table('winter_user_notifications')
        ->where('user_id', $userId)
        ->where('is_read', false)
        ->count();

    return [
        'unreadCount' => $unreadCount
    ];
}

public function onMarkNotificationAsRead()
{
    $notificationId = post('notification_id');
    \Log::info('Notification ID: ' . $notificationId);

    $userId = Auth::getUser()->id;
    \Log::info('User ID: ' . $userId);

    $affectedRows = DB::table('winter_user_notifications')
        ->where('id', $notificationId)
        ->where('user_id', $userId)
        ->update(['is_read' => true, 'updated_at' => now()]);

    \Log::info('Rows updated: ' . $affectedRows);

    if ($affectedRows == 0) {
        \Log::error('Ошибка: уведомление не обновлено');
    }

    return ['message' => 'Уведомление отмечено как прочитанное'];
}

}
