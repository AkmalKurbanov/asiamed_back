<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserEvents extends Migration
{
    public function up()
{
    Schema::rename('winter_user_events_table', 'winter_user_events');
}

public function down()
{
    Schema::rename('winter_user_events', 'winter_user_events_table');
}
}
