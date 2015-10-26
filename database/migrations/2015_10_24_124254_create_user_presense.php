<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPresense extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_presence', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id', false, true);
            $table->timestamp('time');
            $table->integer('status', false, true);
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
        Schema::drop('user_presence');
    }
}
