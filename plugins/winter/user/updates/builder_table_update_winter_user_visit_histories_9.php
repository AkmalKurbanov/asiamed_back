<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories9 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->string('status');
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dropColumn('status');
    });
}
}
