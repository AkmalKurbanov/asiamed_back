<?php namespace Appointment\Services\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateAppointmentServicesPlugin extends Migration
{
    public function up()
{
    Schema::create('appointment_services_plugin', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->string('name');
        $table->binary('desc');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('appointment_services_plugin');
}
}
