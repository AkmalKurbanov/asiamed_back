<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsersGroups3 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users_groups', function($table)
    {
        $table->smallInteger('id');
    });
}

public function down()
{
    Schema::table('winter_user_users_groups', function($table)
    {
        $table->dropColumn('id');
    });
}
}
