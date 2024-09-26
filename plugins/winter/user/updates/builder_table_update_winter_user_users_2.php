<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers2 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->renameColumn('id', 'id ');
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->renameColumn('id ', 'id');
    });
}
}
