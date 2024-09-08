<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateAppointmentPatientsDoctor extends Migration
{
    public function up()
{
    Schema::create('appointment_patients_doctor', function($table)
    {
        $table->engine = 'InnoDB';
        $table->integer('doctor_id');
        $table->integer('patient_id');
    });
}

public function down()
{
    Schema::dropIfExists('appointment_patients_doctor');
}
}
