<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdr', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->integer('user_id', false, true);
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('duration', false, true);
            $table->float('rate');
            $table->float('cost');
            $table->string('ani');
            $table->string('dni');
            $table->timestamp('init_time');
            $table->timestamp('ring_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cdr');
    }
}
