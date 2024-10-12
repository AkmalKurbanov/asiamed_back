<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEvents2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_events', function($table)
    {
        $table->renameColumn('name', 'color');
    });
}

public function down()
{
    Schema::table('winter_user_events', function($table)
    {
        $table->renameColumn('color', 'name');
    });
}
}
