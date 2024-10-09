<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserAppointments5 extends Migration
{
    public function up()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->string('visit_type')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_appointments', function($table)
    {
        $table->dropColumn('visit_type');
    });
}
}
