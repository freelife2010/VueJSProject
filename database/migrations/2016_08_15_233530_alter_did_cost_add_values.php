<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDidCostAddValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('costs_did', function ($table) {
            $table->integer('one_time_value', false, true)->default(0);
            $table->integer('per_month_value', false, true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('costs_did', function ($table) {
            $table->dropColumn('one_time_value');
            $table->dropColumn('per_month_value');
        });
    }
}
