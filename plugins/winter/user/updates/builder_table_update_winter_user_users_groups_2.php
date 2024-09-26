<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsersGroups2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users_groups', function($table)
    {
        $table->primary(['user_id','user_group_id']);
    });
}

public function down()
{
    Schema::table('winter_user_users_groups', function($table)
    {
        $table->dropPrimary(['user_id','user_group_id']);
    });
}
}
