<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCostDidTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('costs_did', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('state')->nullable();
            $table->string('rate_center')->nullable();
            $table->integer('value', false, true)->default(0);
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
        Schema::drop('costs_did');
    }
}
