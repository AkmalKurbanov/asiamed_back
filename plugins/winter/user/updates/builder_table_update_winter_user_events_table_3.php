<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEventsTable3 extends Migration
{
    public function up()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->dateTime('end_time')->nullable();
        $table->dropColumn('color');
    });
}

public function down()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->dropColumn('end_time');
        $table->string('color', 255)->nullable();
    });
}
}
