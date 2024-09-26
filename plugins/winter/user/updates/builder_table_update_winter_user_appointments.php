<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserAppointments extends Migration
{
    public function up()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->time('appointment_time');
        $table->date('appointment_date')->nullable(false)->unsigned(false)->default(null)->change();
    });
}

public function down()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->dropColumn('appointment_time');
        $table->dateTime('appointment_date')->nullable(false)->unsigned(false)->default(null)->change();
    });
}
}
