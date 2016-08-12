<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Ivr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('alias');
            $table->string('parameter')->default('');
            $table->integer('account_id');
            $table->timestamps();
            $table->unique(['alias', 'account_id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ivr');
    }
}
