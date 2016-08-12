<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->integer('country_id', false, true)->nullable();
            $table->dropColumn('user_id');
        });

        Schema::table('app', function ($table) {
            $table->integer('tech_prefix', false, true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('country_id');
            $table->bigInteger('user_id', false, true)->nullable();
        });

        Schema::table('app', function ($table) {
            $table->dropColumn('tech_prefix');
        });
    }
}
