<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFriend extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('friend', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('user_id', false, true);
            $table->timestamp('friend_on');
            $table->timestamp('unfriend_on');
            $table->integer('friend_id', false, true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('friend');
    }
}
