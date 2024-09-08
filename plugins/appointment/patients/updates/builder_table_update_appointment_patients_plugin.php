<?php namespace Appointment\Patients\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateAppointmentPatientsPlugin extends Migration
{
    public function up()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });
}

public function down()
{
    Schema::table('appointment_patients_plugin', function($table)
    {
        $table->dropColumn('created_at');
        $table->dropColumn('updated_at');
        $table->dropColumn('deleted_at');
    });
}
}
