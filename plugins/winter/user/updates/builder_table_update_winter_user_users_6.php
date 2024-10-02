<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateWinterUserUsers6 extends Migration
{
    public function up()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->string('gender')->nullable();
        $table->string('address')->nullable();
        $table->date('birthdate')->nullable();
    });
}

public function down()
{
    Schema::table('winter_user_users', function($table)
    {
        $table->dropColumn('gender');
        $table->dropColumn('address');
        $table->dropColumn('birthdate');
    });
}
}
