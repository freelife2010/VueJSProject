<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupMessage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_message', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('type', false, true);
            $table->integer('from_user_id', false, true);
            $table->timestamp('time');
            $table->json('to_user_id');
            $table->integer('app_id', false, true);
            $table->text('lag');
            $table->text('lng');
            $table->text('url');
            $table->text('filename');
            $table->integer('group_id', false, true);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('group_message');
    }
}
