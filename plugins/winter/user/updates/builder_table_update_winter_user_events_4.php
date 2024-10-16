<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEvents4 extends Migration
{
    public function up()
{
    Schema::table('winter_user_events', function($table)
    {
        $table->string('all_day')->nullable(false)->unsigned(false)->default(null)->change();
    });
}

public function down()
{
    Schema::table('winter_user_events', function($table)
    {
        $table->boolean('all_day')->nullable(false)->unsigned(false)->default(null)->change();
    });
}
}
