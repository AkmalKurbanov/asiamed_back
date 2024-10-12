<?php namespace Winter\User\Models;

use Model;

class Event extends Model
{
    protected $table = 'winter_user_events'; // Имя таблицы с учетом префикса плагина
    protected $fillable = ['title', 'description', 'start_time', 'end_time', 'created_by'];

    // Связь с пользователем, который создал событие
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
