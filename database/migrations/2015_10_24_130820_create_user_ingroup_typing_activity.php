<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserIngroupTypingActivity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_ingroup_typing_activity', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id', false, true);
            $table->timestamp('starttime');
            $table->timestamp('finish');
            $table->integer('group_id', false, true);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_ingroup_typing_activity');
    }
}
