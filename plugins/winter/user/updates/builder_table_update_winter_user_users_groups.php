<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsersGroups extends Migration
{
    public function up()
{
    Schema::rename('winter_users_groups', 'winter_user_users_groups');
}

public function down()
{
    Schema::rename('winter_user_users_groups', 'winter_users_groups');
}
}
