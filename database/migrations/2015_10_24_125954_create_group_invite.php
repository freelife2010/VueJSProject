<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupInvite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_invite', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('group_id', false, true);
            $table->integer('from_user', false, true);
            $table->integer('to_user', false, true);
            $table->boolean('accepted');
            $table->timestamp('invite_on');
            $table->timestamp('accepted_on');
            $table->boolean('rejected');
            $table->boolean('cancelled');
            $table->integer('cancelled_by_user', false, true);
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
        Schema::drop('group_invite');
    }
}
