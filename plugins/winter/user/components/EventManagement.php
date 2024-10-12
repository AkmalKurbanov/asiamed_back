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
            $event->start_time = Carbon::parse($data['start_time']);
            $event->end_time = !empty($data['end_time']) ? Carbon::parse($data['end_time']) : null;
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
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => Carbon::parse($event->start_time)->toIso8601String(),
                'end' => $event->end_time ? Carbon::parse($event->end_time)->toIso8601String() : null,
                'description' => $event->description,
                'backgroundColor' => $event->color ?? '#3c8dbc',
                'borderColor' => $event->color ?? '#3c8dbc',
                'extendedProps' => [
                    'event_id' => $event->id // Добавляем event_id в extendedProps
                ]
            ];
        });

        \Log::info('Загруженные события:', $events->toArray());

        return ['events' => $events];
    } catch (\Exception $e) {
        \Log::error('Ошибка при загрузке событий: ' . $e->getMessage());
        throw new ApplicationException('Ошибка при загрузке событий');
    }
}


    public function onUpdateEvent()
    {
        $data = post();

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
            $event->start_time = !empty($data['start_time']) ? Carbon::parse($data['start_time']) : $event->start_time;
            $event->end_time = !empty($data['end_time']) ? Carbon::parse($data['end_time']) : $event->end_time;
            $event->save();

            return ['error' => false, 'message' => 'Событие успешно обновлено.'];
        } catch (\Exception $e) {
            \Log::error('Ошибка при обновлении события: ' . $e->getMessage());
            return ['error' => true, 'message' => 'Произошла ошибка при обновлении события. Пожалуйста, повторите попытку.'];
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
