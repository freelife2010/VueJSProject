<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQueueTableAddAudioCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('queue', function ($table) {
            $table->string('client_waiting_audio')->nullable();
            $table->string('agent_waiting_audio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('queue', function ($table) {
            $table->dropColumn('client_waiting_audio');
            $table->dropColumn('agent_waiting_audio');
        });
    }
}
