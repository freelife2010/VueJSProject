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
            $table->string('name');
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
            'Conference',
            'Hang Up',
            'Forward to user',
            'Forward to number',
            'Stream Audio',
            'Voicemail',
            'IVR',
            'Playback File',
            'Playback TTS',
            'Playback URL',
            'Queue',
            'Dequeue',
            'HTTP Action Request'
        ];
        foreach ($actions as $action) {
            DB::table('did_action')->insert(
                [
                    'name'    => $action
                ]
            );
        }
    }
}
