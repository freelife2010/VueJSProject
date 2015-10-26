<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('queue_id', false, true);
            $table->integer('greeting_file_id', false, true);
            $table->integer('wait_msg_id', false, true);
            $table->integer('play_interval', false, true);
            $table->integer('connect_msg_id', false, true);
            $table->timestamp('created_on');
            $table->timestamp('modified_on');
            $table->integer('created_by', false, true);
            $table->integer('user_id', false, true);
            $table->string('queue_name');
            $table->string('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('queue');
    }
}
