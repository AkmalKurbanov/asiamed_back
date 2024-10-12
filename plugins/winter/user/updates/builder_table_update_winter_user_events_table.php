<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEventsTable extends Migration
{
    public function up()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->text('description');
    });
}

public function down()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->dropColumn('description');
    });
}
}
