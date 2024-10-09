<?php namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\Event;

class EventCalendar extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'EventCalendar Component',
            'description' => 'Displays a calendar of events'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        // Здесь можно загрузить события из базы данных
        $this->events = Event::all();
    }

    public function onAddEvent()
    {
        // Логика добавления события
        $event = new Event();
        $event->title = post('title');
        $event->color = post('color');
        $event->start_time = now(); // Здесь можно установить нужное время начала события
        $event->save();

        return [
            'success' => true,
            'message' => 'Событие успешно добавлено.'
        ];
    }
}
