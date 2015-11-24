<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDidTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('did');
            $table->integer('app_id', false, true)->default(0);
            $table->string('reserve_id');
            $table->string('did_type')->default('');
            $table->string('state')->default('');
            $table->string('npa')->default('');
            $table->string('nxx')->default('');
            $table->string('rate_center')->default('');
            $table->integer('account_id', false, true);
            $table->integer('action_id', false, true);
            $table->timestamps();

            $table->index('app_id', 'app_id');
            $table->index('action_id', 'action_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('did');
    }
}
