<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentPatientsPlugin5 extends Migration
{
    public function up()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->renameColumn('date', 'appointment_date');
    });
}

public function down()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->renameColumn('appointment_date', 'date');
    });
}
}
