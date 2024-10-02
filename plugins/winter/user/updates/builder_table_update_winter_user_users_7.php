<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers7 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->dateTime('birthdate')->nullable()->unsigned(false)->default(null)->change();
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->date('birthdate')->nullable()->unsigned(false)->default(null)->change();
    });
}
}
