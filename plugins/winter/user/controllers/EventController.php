<?php namespace Winter\User\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Winter\User\Models\Event;
use Illuminate\Http\Request;
use Winter\User\Facades\Auth;

class EventController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Winter.User', 'user', 'eventcontroller');
    }

    public function addEvent(Request $request)
    {   

        \Log::info('Adding event', ['title' => $request->input('title'), 'color' => $request->input('color')]);
        // Проверьте права доступа
        if (!Auth::getUser()->is_superuser && !Auth::getUser()->groups()->where('code', 'admins')->exists()) {
            return response()->json(['success' => false, 'message' => 'Нет прав для добавления событий.']);
        }

        // Создайте и сохраните новое событие
        $event = new Event();
        $event->title = $request->input('title');
        $event->color = $request->input('color');
        $event->start_time = now(); // Здесь можно установить нужное время начала события
        $event->save();

        return response()->json(['success' => true, 'message' => 'Событие успешно добавлено.']);
    }

    public function index()
{
    return $this->asExtension('Page')->setLayout('adminLTE')->setView('index')->run();
}
}
