<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories5 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->renameColumn('description', 'notes');
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->renameColumn('notes', 'description');
    });
}
}
