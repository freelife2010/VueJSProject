<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupActivity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_activity', function (Blueprint $table) {
            $table->increments('group_id')->unsigned();
            $table->integer('user_id', false, true);
            $table->timestamp('time');
            $table->integer('type', false, true);
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
        Schema::drop('group_activity');
    }
}
