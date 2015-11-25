<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDidsParameters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_actions_parameters', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('did_id', false, true);
            $table->integer('action_id', false, true)->default(0);
            $table->integer('parameter_id', false, true)->default(0);
            $table->string('parameter_value')->default('');
            $table->timestamps();

            $table->index(['action_id', 'parameter_id'], 'action_parameter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('did_actions_parameters');
    }
}
