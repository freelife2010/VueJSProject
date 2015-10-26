<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_group', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id', false, true);
            $table->json('members');
            $table->string('name');
            $table->string('desc');
            $table->integer('app_id', false, true);
            $table->boolean('is_public');
            $table->integer('max_member', false, true);
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
        Schema::drop('user_group');
    }
}
