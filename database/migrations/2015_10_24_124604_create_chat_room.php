<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatRoom extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_room', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id', false, true);
            $table->timestamp('time');
            $table->json('members');
            $table->string('name');
            $table->string('desc');
            $table->integer('app_id', false, true);
            $table->integer('max_member', false, true);
            $table->timestamp('created');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('chat_room');
    }
}
