<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEventsTable2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->string('title', 255)->nullable()->change();
        $table->text('description')->nullable()->change();
    });
}

public function down()
{
    Schema::table('winter_user_events_table', function($table)
    {
        $table->string('title', 255)->nullable(false)->change();
        $table->text('description')->nullable(false)->change();
    });
}
}
