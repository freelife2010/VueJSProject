<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DidAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_action', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('action');
            $table->string('parameter')->default('');
            $table->string('second_parameter')->default('');
        });

        $this->seedTable();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('did_action');
    }

    private function seedTable()
    {
        $actions = [
            'Conference' => ''
        ];
        foreach ($actions as $action => $parameter) {
            DB::table('did_action')->insert(
                [
                    'action'    => $action,
                    'parameter' => $parameter
                ]
            );
        }
    }
}
