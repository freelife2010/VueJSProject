<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRealTimeQueueAgent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('real_time_queue_agent', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('queue_id', false, true);
            $table->integer('agent_user_id', false, true);
            $table->integer('status', false, true);
            $table->timestamp('created_on');
            $table->timestamp('modified_on');
            $table->integer('last_wait_on', false, true);
            $table->integer('talk_start_on', false, true);
            $table->string('caller');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('real_time_queue_agent');
    }
}
