<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentPatientsPlugin2 extends Migration
{
    public function up()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->integer('doctor_id');
    });
}

public function down()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->dropColumn('doctor_id');
    });
}
}
