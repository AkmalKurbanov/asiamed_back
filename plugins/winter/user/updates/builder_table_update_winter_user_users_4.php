<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers4 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->boolean('is_active')->nullable(false)->unsigned(false)->default(true)->change();
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->smallInteger('is_active')->nullable(false)->unsigned(false)->default(null)->change();
    });
}
}
