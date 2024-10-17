<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserNotifications2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_notifications', function($table)
    {
        $table->string('category');
    });
}

public function down()
{
    Schema::table('winter_user_notifications', function($table)
    {
        $table->dropColumn('category');
    });
}
}
