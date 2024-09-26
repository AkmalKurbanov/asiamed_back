<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers5 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->boolean('is_guest')->default(true)->change();
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->boolean('is_guest')->default(0)->change();
    });
}
}
