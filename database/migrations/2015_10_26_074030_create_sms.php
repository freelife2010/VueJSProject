<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('user_id', false, true);
            $table->timestamp('start');
            $table->timestamp('delivered_on');
            $table->timestamp('confirmed_on');
            $table->integer('status', false, true);
            $table->timestamp('received_on');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sms');
    }
}
