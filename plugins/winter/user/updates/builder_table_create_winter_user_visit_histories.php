<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateWinterUserVisitHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('winter_user_visit_histories', function ($table) {
            $table->increments('id');
            $table->integer('patient_id')->unsigned();
            $table->integer('doctor_id')->unsigned();
            $table->text('visit_notes'); // Заметки врача по посещению
            $table->timestamp('visit_date'); // Дата посещения
            $table->timestamps();

            // Связи с таблицей пользователей
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('winter_user_visit_histories');
    }
}
