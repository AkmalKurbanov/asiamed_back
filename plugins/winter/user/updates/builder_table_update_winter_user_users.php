<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->string('iu_telephone')->nullable();
        $table->string('iu_job')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->dropColumn('iu_telephone');
        $table->dropColumn('iu_job');
    });
}
}
