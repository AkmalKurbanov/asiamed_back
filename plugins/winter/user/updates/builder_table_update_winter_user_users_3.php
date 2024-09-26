<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers3 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->smallInteger('is_active');
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->dropColumn('is_active');
    });
}
}
