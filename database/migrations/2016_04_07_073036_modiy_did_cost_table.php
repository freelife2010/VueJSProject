<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModiyDidCostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('costs_did', function ($table) {
            $table->integer('country_id', false, true)->nullable();
            $table->index('country_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('costs_did', 'country_id'))
            Schema::table('costs_did', function ($table) {
                $table->dropColumn('country_id');
                $table->dropIndex('country_id');
            });
    }
}
