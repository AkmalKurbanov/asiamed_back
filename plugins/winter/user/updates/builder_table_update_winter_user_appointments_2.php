<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserAppointments2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->date('appointment_date')->nullable()->change();
        $table->time('appointment_time')->nullable()->change();
    });
}

public function down()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->date('appointment_date')->nullable(false)->change();
        $table->time('appointment_time')->nullable(false)->change();
    });
}
}
