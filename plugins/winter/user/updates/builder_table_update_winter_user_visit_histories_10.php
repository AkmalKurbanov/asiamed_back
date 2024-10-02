<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserVisitHistories10 extends Migration
{
    public function up()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->dateTime('visit_date')->nullable(false)->unsigned(false)->default(null)->change();
    });
}

public function down()
{
    Schema::table('winter_user_visit_histories', function($table)
    {
        $table->text('visit_date')->nullable(false)->unsigned(false)->default(null)->change();
    });
}
}
