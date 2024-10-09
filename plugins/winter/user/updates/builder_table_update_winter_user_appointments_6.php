<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserAppointments6 extends Migration
{
    public function up()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->integer('doctor_id')->nullable()->change();
    });
}

public function down()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->integer('doctor_id')->nullable(false)->change();
    });
}
}
