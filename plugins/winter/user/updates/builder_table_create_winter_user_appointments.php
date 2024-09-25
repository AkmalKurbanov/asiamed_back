<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateWinterUserAppointments extends Migration
{
    public function up()
    {
        Schema::create('winter_user_appointments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->dateTime('appointment_date');
            $table->timestamps();  // Это автоматически добавляет `created_at` и `updated_at`
            $table->softDeletes(); // Это автоматически добавляет `deleted_at`

            // Внешние ключи
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('winter_user_appointments');
    }
}
