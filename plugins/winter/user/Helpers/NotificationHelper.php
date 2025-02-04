<?php

namespace Winter\User\Helpers;

use Illuminate\Support\Facades\DB;

class NotificationHelper
{
    public static function createNotification($userId, $entityId, $message, $category, $type)
{
    
    
    
    

    try {
        DB::table('winter_user_notifications')->insert([
            'user_id' => $userId,
            'type' => $type,
            'entity_id' => $entityId,
            'category' => $category,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        
    } catch (\Exception $e) {
        \Log::error('Ошибка при создании уведомления: ' . $e->getMessage());
    }
}

}
