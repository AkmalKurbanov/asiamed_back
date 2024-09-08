<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentPatientsPlugin3 extends Migration
{
    public function up()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->dateTime('data')->nullable();
    });
}

public function down()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->dropColumn('data');
    });
}
}
