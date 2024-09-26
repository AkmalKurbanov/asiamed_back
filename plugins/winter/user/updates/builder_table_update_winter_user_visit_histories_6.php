<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories6 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->text('diagnosis');
        $table->text('prescription');
        $table->dropColumn('notes');
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dropColumn('diagnosis');
        $table->dropColumn('prescription');
        $table->dateTime('notes');
    });
}
}
