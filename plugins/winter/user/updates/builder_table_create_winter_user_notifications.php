<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateWinterUserNotifications extends Migration
{
    public function up()
{
    Schema::create('winter_user_notifications', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->integer('user_id');
        $table->string('type');
        $table->integer('entity_id');
        $table->boolean('is_read');
    });
}

public function down()
{
    Schema::dropIfExists('winter_user_notifications');
}
}
