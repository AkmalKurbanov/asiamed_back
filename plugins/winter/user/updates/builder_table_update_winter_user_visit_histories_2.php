<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->renameColumn('patient_id', 'patient_id1');
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->renameColumn('patient_id1', 'patient_id');
    });
}
}
