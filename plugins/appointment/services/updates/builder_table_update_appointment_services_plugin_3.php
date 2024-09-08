<?php namespace Appointment\Services\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentServicesPlugin3 extends Migration
{
    public function up()
{
    Schema::table('appointment_services_plugin', function($table)
    {
        $table->text('appointment_date')->nullable()->change();
    });
}

public function down()
{
    Schema::table('appointment_services_plugin', function($table)
    {
        $table->text('appointment_date')->nullable(false)->change();
    });
}
}
