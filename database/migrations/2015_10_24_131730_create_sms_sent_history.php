<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsSentHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_sent_history', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('text');
            $table->integer('status', false, true);
            $table->string('sent_to');
            $table->string('sent_from');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sms_sent_history');
    }
}
