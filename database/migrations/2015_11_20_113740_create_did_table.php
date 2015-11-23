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
            $table->string('reserve_id');
            $table->string('did_type')->default('');
            $table->string('state')->default('');
            $table->string('npa')->default('');
            $table->string('nxx')->default('');
            $table->string('rate_center')->default('');
            $table->integer('account_id', false, true);
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
        Schema::drop('did');
    }
}
