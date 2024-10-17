<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserNotifications extends Migration
{
    public function up()
{
    Schema::table('winter_user_notifications', function($table)
    {
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_notifications', function($table)
    {
        $table->dropColumn('created_at');
        $table->dropColumn('updated_at');
        $table->dropColumn('deleted_at');
    });
}
}
