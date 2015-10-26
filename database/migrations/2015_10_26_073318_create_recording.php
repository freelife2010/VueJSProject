<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecording extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recording', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->text('filename');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('duration', false, true);
            $table->integer('type', false, true);
            $table->integer('user_id', false, true);
            $table->integer('recording_id', false, true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('recording');
    }
}
