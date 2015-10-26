<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrivateNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('private_number', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('number');
            $table->timestamp('starttime');
            $table->timestamp('last_modified');
            $table->integer('handle_method', false, true);
            $table->string('optxml_url');
            $table->string('number_forward');
            $table->integer('app', false, true);
            $table->integer('user_id', false, true);
            $table->integer('conference_id', false, true);
            $table->integer('queue_id', false, true);
            $table->integer('ivr_id', false, true);
            $table->string('from_ip');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('private_number');
    }
}
