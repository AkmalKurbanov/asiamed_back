<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserGroups extends Migration
{
    public function up()
{
    Schema::table('winter_user_groups', function($table)
    {
        $table->renameColumn('id', 'id ');
    });
}

public function down()
{
    Schema::table('winter_user_groups', function($table)
    {
        $table->renameColumn('id ', 'id');
    });
}
}
