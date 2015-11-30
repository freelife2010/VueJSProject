<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQueueCallerSession extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue_caller_session', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('queue_id', false, true);
            $table->string('queue_name');
            $table->string('uuid');
            $table->timestamp('join_time');
            $table->timestamp('leave_time');
            $table->timestamp('talk_start_time');
            $table->integer('deposition', false, true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('queue_caller_session');
    }
}
