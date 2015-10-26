<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupMessageReadBy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_message_read_by', function (Blueprint $table) {
            $table->increments('group_msg_id')->unsigned();
            $table->integer('user_id', false, true);
            $table->timestamp('read_on');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('group_message_read_by');
    }
}
