<?php namespace Appointment\Services\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentServicesPlugin extends Migration
{
    public function up()
{
    Schema::table('appointment_services_plugin', function($table)
    {
        $table->text('icon');
    });
}

public function down()
{
    Schema::table('appointment_services_plugin', function($table)
    {
        $table->dropColumn('icon');
    });
}
}
