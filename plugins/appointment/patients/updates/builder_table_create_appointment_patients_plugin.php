<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateAppointmentPatientsPlugin extends Migration
{
    public function up()
{
    Schema::create('appointment_patients_plugin', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->string('name');
        $table->string('phone');
        $table->string('email');
    });
}

public function down()
{
    Schema::dropIfExists('appointment_patients_plugin');
}
}
