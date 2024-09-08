<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentPatientsPlugin4 extends Migration
{
    public function up()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->renameColumn('data', 'date');
    });
}

public function down()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->renameColumn('date', 'data');
    });
}
}
