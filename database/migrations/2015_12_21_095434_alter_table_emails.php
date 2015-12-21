<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEmails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('emails', function ($table) {
            $table->string('from_name')->nullable();
            $table->string('from_address')->nullable();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port', false, true)->nullable();
            $table->string('smtp_user')->nullable();
            $table->string('smtp_password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('emails', function ($table) {
            $table->dropColumn('from');
            $table->dropColumn('smtp_host');
            $table->dropColumn('smtp_port');
            $table->dropColumn('smtp_user');
            $table->dropColumn('smtp_password');
        });
    }
}
