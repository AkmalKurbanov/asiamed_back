<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserNotifications3 extends Migration
{
    public function up()
{
    Schema::table('winter_user_notifications', function($table)
    {
        $table->string('category', 255)->nullable()->change();
    });
}

public function down()
{
    Schema::table('winter_user_notifications', function($table)
    {
        $table->string('category', 255)->nullable(false)->change();
    });
}
}
