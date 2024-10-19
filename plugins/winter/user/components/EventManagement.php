<?php
namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\User;
use Winter\User\Models\Event;
use Auth;
use ApplicationException;
use Carbon\Carbon;
Carbon::setLocale('ru');
use Illuminate\Support\Facades\DB;

class EventManagement extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Управление событиями',
            'description' => 'Компонент для создания, редактирования и управления событиями',
        ];
    }

    public function onRun()
    {
        $this->page['events'] = $this->onLoadEvents()['events'];
    }

    // Создание события (только для администраторов)
    public function onCreateEvent()
    {
        $this->checkAdmin(); // Проверка прав администратора

        $data = post();

         \Log::info('Полученные данные для создания события: ' . json_encode($data));

        if (empty($data['title']) || empty($data['start_time'])) {
            throw new ApplicationException('Необходимо заполнить название и время начала');
        }

        try {
            $event = new Event();
            $event->title = $data['title'];
            $event->description = $data['description'] ?? '';
            $event->start_time = Carbon::parse($data['start_time'])->setTimezone('UTC');
            $event->end_time = !empty($data['end_time']) ? Carbon::parse($data['end_time'])->setTimezone('UTC') : null;
            $event->all_day = post('all_day', 0);
            $event->color = $data['color'] ?? '#3c8dbc';
            $event->created_by = Auth::getUser()->id;
            $event->save();

            // Создание уведомления о создании события с помощью хелпера
            NotificationHelper::createNotification($event->id, 'Новое событие: ' . $event->title, 'События', 'event_created');


            return ['error' => false, 'message' => 'Событие успешно создано.', 'event_id' => $event->id];
        } catch (\Exception $e) {
            \Log::error('Ошибка при создании события: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Произошла ошибка при создании события.'];
        }
    }

    // Загрузка событий
    public function onLoadEvents()
    {
        try {
            $events = Event::all()->map(function ($event) {
                $startTime = Carbon::parse($event->start_time, 'UTC')->setTimezone('Asia/Bishkek')->toIso8601String();
                $endTime = $event->end_time ? Carbon::parse($event->end_time, 'UTC')->setTimezone('Asia/Bishkek')->toIso8601String() : null;

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start' => $startTime,
                    'end' => $endTime,
                    'allDay' => $event->all_day,
                    'backgroundColor' => $event->color,
                    'borderColor' => $event->color,
                    'editable' => $this->canEdit(), // Только админы могут редактировать
                    'extendedProps' => [
                        'event_id' => $event->id
                    ]
                ];
            });

            return ['events' => $events];
        } catch (\Exception $e) {
            throw new ApplicationException('Ошибка при загрузке событий');
        }
    }

    // Обновление события (только для администраторов)
    public function onUpdateEvent()
    {
        $this->checkAdmin(); // Проверка прав администратора

        $data = post();
        \Log::info('Полученные данные для обновления события:', $data);

        if (empty($data['event_id'])) {
            throw new ApplicationException('ID события не указан.');
        }

        try {
            $event = Event::find($data['event_id']);
            if (!$event) {
                throw new ApplicationException('Событие не найдено.');
            }

            $event->title = $data['title'] ?? $event->title;
            $event->description = $data['description'] ?? $event->description;
            $event->start_time = Carbon::parse($data['start_time'])->setTimezone('UTC');
            $event->end_time = !empty($data['end_time']) ? Carbon::parse($data['end_time'])->setTimezone('UTC') : null;
            $event->save();

            // Создание уведомления об обновлении события с помощью хелпера
            NotificationHelper::createNotification($event->id, 'Событие обновлено: ' . $event->title, 'События', 'event_updated'); 


            return ['error' => false, 'message' => 'Событие успешно обновлено.'];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Произошла ошибка при обновлении события.'];
        }
    }

    // Удаление события (только для администраторов)
    public function onDeleteEvent()
    {
        $this->checkAdmin(); // Проверка прав администратора

        $eventId = post('event_id');

        if (!$eventId || !$event = Event::find($eventId)) {
            throw new ApplicationException('Событие не найдено.');
        }

        try {
            $eventTitle = $event->title; // Добавляем получение заголовка события
            $event->delete();

            // Создание уведомления об удалении события с помощью хелпера
            NotificationHelper::createNotification($eventId, 'Событие удалено: ' . $eventTitle, 'События', 'event_deleted');

            return ['error' => false, 'message' => 'Событие успешно удалено.'];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Произошла ошибка при удалении события.'];
        }
    }

    // Проверка прав пользователя (только для администраторов)
    protected function checkAdmin()
    {
        $currentUser = Auth::getUser();
        if (!$currentUser->is_superuser && !$currentUser->groups()->where('code', 'admins')->exists()) {
            throw new ApplicationException('У вас нет прав для выполнения этого действия.');
        }
    }

    // Проверка, может ли текущий пользователь редактировать события
    protected function canEdit()
    {
        $currentUser = Auth::getUser();
        return $currentUser->is_superuser || $currentUser->groups()->where('code', 'admins')->exists();
    }



    protected function createNotification($entityId, $message, $category, $type = 'event')
    {
        // Выбираем пользователей, которым нужно отправить уведомление
        $users = User::whereHas('groups', function ($query) {
            $query->whereIn('code', ['doctors', 'admins']); // Врачи и администраторы
        })->get();

        foreach ($users as $user) {
            DB::table('winter_user_notifications')->insert([
                'user_id' => $user->id,
                'type' => $type, // Передаем тип уведомления
                'entity_id' => $entityId,
                'category' => $category,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }



}
