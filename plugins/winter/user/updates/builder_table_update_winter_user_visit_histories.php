<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->integer('patient_id')->unsigned();
        $table->integer('doctor_id')->unsigned();
        $table->text('visit_notes');
        $table->dateTime('visit_date');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dropColumn('patient_id');
        $table->dropColumn('doctor_id');
        $table->dropColumn('visit_notes');
        $table->dropColumn('visit_date');
        $table->dropColumn('created_at');
        $table->dropColumn('updated_at');
    });
}
}
