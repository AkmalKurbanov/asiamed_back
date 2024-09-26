<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableDeleteWinterUserUsersGroups extends Migration
{
    public function up()
{
    Schema::dropIfExists('winter_user_users_groups');
}

public function down()
{
    Schema::create('winter_user_users_groups', function($table)
    {
        $table->engine = 'InnoDB';
        $table->integer('user_id')->unsigned();
        $table->integer('user_group_id')->unsigned();
        $table->smallInteger('id');
        $table->primary(['user_id','user_group_id']);
    });
}
}
