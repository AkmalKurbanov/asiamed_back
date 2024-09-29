<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserAppointments3 extends Migration
{
    public function up()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->integer('user_id')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->dropColumn('user_id');
    });
}
}
