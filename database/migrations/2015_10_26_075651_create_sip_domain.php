<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSipDomain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sip_domain', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('app_id', false, true);
            $table->string('domain_name');
            $table->timestamp('modified_on');
            $table->timestamp('created_on');
            $table->integer('domain_id', false, true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sip_domain');
    }
}
