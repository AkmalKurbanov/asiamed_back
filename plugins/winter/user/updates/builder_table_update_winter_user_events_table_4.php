<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEventsTable4 extends Migration
{
    public function up()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->string('created_by')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->dropColumn('created_by');
    });
}
}
