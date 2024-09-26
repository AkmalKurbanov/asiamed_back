<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories7 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->text('notes');
        $table->dropColumn('diagnosis');
        $table->dropColumn('prescription');
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dropColumn('notes');
        $table->text('diagnosis');
        $table->text('prescription');
    });
}
}
