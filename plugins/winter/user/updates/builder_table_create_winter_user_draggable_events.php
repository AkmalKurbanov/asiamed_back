<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateWinterUserDraggableEvents extends Migration
{
    public function up()
{
    Schema::create('winter_user_draggable_events', function($table)
    {
        $table->engine = 'InnoDB';
        $table->increments('id')->unsigned();
        $table->string('title')->nullable();
        $table->string('color')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('winter_user_draggable_events');
}
}
