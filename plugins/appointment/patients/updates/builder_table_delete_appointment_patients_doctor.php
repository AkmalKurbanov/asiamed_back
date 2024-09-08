<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableDeleteAppointmentPatientsDoctor extends Migration
{
    public function up()
{
    Schema::dropIfExists('appointment_patients_doctor');
}

public function down()
{
    Schema::create('appointment_patients_doctor', function($table)
    {
        $table->engine = 'InnoDB';
        $table->integer('doctor_id');
        $table->integer('patient_id');
    });
}
}
