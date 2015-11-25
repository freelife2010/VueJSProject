<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('uuid')->default('');
            $table->integer('tech_prefix', false, true)->unique()->default(0);
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->default('');
            $table->string('password');
            $table->integer('app_id', false, true);
            $table->integer('last_status', false, true)->default(1);
            $table->integer('activated', false, true)->default(1);
            $table->string('last_ip')->default('');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
