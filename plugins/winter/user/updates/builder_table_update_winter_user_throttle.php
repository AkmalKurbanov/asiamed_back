<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserThrottle extends Migration
{
    public function up()
{
    Schema::table('winter_user_throttle', function($table)
    {
        $table->renameColumn('id', 'id ');
    });
}

public function down()
{
    Schema::table('winter_user_throttle', function($table)
    {
        $table->renameColumn('id ', 'id');
    });
}
}
