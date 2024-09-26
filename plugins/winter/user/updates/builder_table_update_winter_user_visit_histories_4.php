<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories4 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dateTime('description');
        $table->text('visit_date')->nullable(false)->unsigned(false)->default(null)->change();
        $table->dropColumn('visit_notes');
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dropColumn('description');
        $table->dateTime('visit_date')->nullable(false)->unsigned(false)->default(null)->change();
        $table->text('visit_notes');
    });
}
}
