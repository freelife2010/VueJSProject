<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConference extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conference', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('conference_id', false, true);
            $table->timestamp('created_on');
            $table->integer('owner_user_id', false, true);
            $table->string('owner_code');
            $table->string('participant_code');
            $table->integer('require_owner_to_start', false, true);
            $table->integer('greeting_media_id', false, true);
            $table->integer('join_media_id', false, true);
            $table->integer('require_ident', false, true);
            $table->integer('recording', false, true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('conference');
    }
}
