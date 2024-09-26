<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateWinterUserUsersGroups extends Migration
{
    public function up()
{
    Schema::create('winter_user_users_groups', function($table)
    {
        $table->engine = 'InnoDB';
        $table->integer('user_id')->unsigned();
        $table->integer('user_group_id')->unsigned();
    });
}

public function down()
{
    Schema::dropIfExists('winter_user_users_groups');
}
}
