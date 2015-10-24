<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTouserTypingActivity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_touser_typing_activity', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->timestamp('starttime');
            $table->timestamp('finish');
            $table->integer('user_id', false, true);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_touser_typing_activity');
    }
}
