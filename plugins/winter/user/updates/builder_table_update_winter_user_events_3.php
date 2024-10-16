<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEvents3 extends Migration
{
    public function up()
{
    Schema::table('winter_user_events', function($table)
    {
        $table->boolean('all_day');
    });
}

public function down()
{
    Schema::table('winter_user_events', function($table)
    {
        $table->dropColumn('all_day');
    });
}
}
