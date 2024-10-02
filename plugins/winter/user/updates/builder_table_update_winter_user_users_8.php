<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers8 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->text('doctor_id')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->dropColumn('doctor_id');
    });
}
}
