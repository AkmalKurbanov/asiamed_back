<?php
namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\User;
use Auth;
use ApplicationException;
use Carbon\Carbon;
use Winter\User\Models\Event;

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

    public function onCreateEvent()
    {
        $data = post();

        \Log::info('Полученные данные для создания события:', $data);

        if (empty($data['title']) || empty($data['start_time'])) {
            \Log::error('Не заполнены обязательные поля: title или start_time');
            throw new ApplicationException('Необходимо заполнить название и время начала');
        }

        try {
            $event = new Event();
            $event->title = $data['title'];
            $event->description = $data['description'] ?? '';

            // Конвертируем время в UTC перед сохранением
            $event->start_time = Carbon::parse($data['start_time'], 'Asia/Bishkek')->setTimezone('UTC');
            $event->end_time = !empty($data['end_time']) ? Carbon::parse($data['end_time'], 'Asia/Bishkek')->setTimezone('UTC') : null;
            $event->all_day = post('all_day', 0);
            \Log::info(post());
            \Log::info('Время, сохранённое в UTC (start_time): ' . $event->start_time);

            $event->color = $data['color'] ?? '#3c8dbc';
            $event->created_by = Auth::getUser()->id;
            $event->save();

            \Log::info('Событие успешно создано: ID ' . $event->id);

            return ['error' => false, 'message' => 'Событие успешно создано.', 'event_id' => $event->id];
        } catch (\Exception $e) {
            \Log::error('Ошибка при создании события: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Произошла ошибка при создании события. Пожалуйста, повторите попытку.'];
        }
    }
    
    public function onLoadEvents()
    {
        try {
            $events = Event::all()->map(function ($event) {
                // Конвертируем время из UTC в локальную временную зону Asia/Bishkek
                $startTime = Carbon::parse($event->start_time, 'UTC')->setTimezone('Asia/Bishkek')->toIso8601String();
                $endTime = $event->end_time ? Carbon::parse($event->end_time, 'UTC')->setTimezone('Asia/Bishkek')->toIso8601String() : null;
                
                // Логируем для проверки
                \Log::info('Загруженное событие: ID ' . $event->id);
                \Log::info('Start time (UTC -> Asia/Bishkek): ' . $startTime);
                \Log::info('End time (UTC -> Asia/Bishkek): ' . ($endTime ?? 'null'));

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $startTime,  // Отправляем конвертированное время
                    'end' => $endTime,      // Отправляем конвертированное время окончания (если есть)
                    'allDay' => $event->all_day,
                    'backgroundColor' => $event->color,
                    'borderColor' => $event->color,
                    'editable' => true,
                    'extendedProps' => [
                        'event_id' => $event->id
                    ]
                ];
            });

            return ['events' => $events];
        } catch (\Exception $e) {
            \Log::error('Ошибка при загрузке событий: ' . $e->getMessage());
            throw new ApplicationException('Ошибка при загрузке событий');
        }
    }

    public function onUpdateEvent()
{
    $data = post();

    \Log::info('Полученные данные для обновления события:', $data);

    if (empty($data['event_id'])) {
        \Log::error('ID события не указан.');
        throw new ApplicationException('ID события не указан.');
    }

    try {
        $event = Event::find($data['event_id']);
        if (!$event) {
            \Log::error('Событие не найдено.');
            throw new ApplicationException('Событие не найдено.');
        }

        $event->title = $data['title'] ?? $event->title;
        $event->description = $data['description'] ?? $event->description;
        $event->start_time = Carbon::parse($data['start_time'])->format('Y-m-d H:i:s');
        if (!empty($data['end_time'])) {
            $event->end_time = Carbon::parse($data['end_time'])->format('Y-m-d H:i:s');
        } else {
            $event->end_time = null;  // Если end_time пустое, очищаем его
        }

        // Логирование для проверки
        \Log::info('Сохраненное время (start_time): ' . $event->start_time);
        \Log::info('Сохраненное время (end_time): ' . ($event->end_time ?? 'null'));

        $event->save();

        \Log::info('Событие успешно обновлено: ID ' . $event->id);

        return ['error' => false, 'message' => 'Событие успешно обновлено.'];
    } catch (\Exception $e) {
        \Log::error('Ошибка при обновлении события: ' . $e->getMessage());
        return ['error' => true, 'message' => 'Произошла ошибка при обновлении события.'];
    }
}



    public function onDeleteEvent()
    {
        $eventId = post('event_id');

        if (!$eventId || !$event = Event::find($eventId)) {
            throw new ApplicationException('Событие не найдено.');
        }

        try {
            $event->delete();

            return ['error' => false, 'message' => 'Событие успешно удалено.'];
        } catch (\Exception $e) {
            \Log::error('Ошибка при удалении события: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Произошла ошибка при удалении события. Пожалуйста, повторите попытку.'];
        }
    }
}
