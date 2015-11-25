<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_action_parameters', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->integer('action_id', false, true);
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
        Schema::drop('did_action_parameters');
    }

    private function seedTable() {

        $params = $this->getParamsList();

        foreach ($params as $param) {
            $action = DB::table('did_action')->whereName($param['action'])->first();
            DB::table('did_action_parameters')->insert(
                [
                    'name'    => $param['name'],
                    'action_id' => $action->id
                ]
            );
        }
    }

    private function getParamsList()
    {
        return [
            [
                'name' => 'Conference Alias',
                'action' => 'Conference'
            ],
            [
                'name' => 'APP user id',
                'action' => 'Forward to user'
            ],
            [
                'name' => 'Phone Number',
                'action' => 'Forward to number'
            ],
            [
                'name' => 'URL to an audio stream',
                'action' => 'Stream Audio'
            ],
            [
                'name' => 'APP user id (or the user that own the voice mail box)',
                'action' => 'Voicemail'
            ],
            [
                'name' => 'Greeting prompt',
                'action' => 'IVR'
            ],
            [
                'name' => 'Invalid prompt',
                'action' => 'IVR'
            ],
            [
                'name' => 'Key-Action',
                'action' => 'IVR'
            ],
            [
                'name' => 'Filename',
                'action' => 'Playback File'
            ],
            [
                'name' => 'Text, language',
                'action' => 'Playback TTS'
            ],
            [
                'name' => 'URL of the voice file',
                'action' => 'Playback URL'
            ],
            [
                'name' => 'Alias of the queue',
                'action' => 'Queue'
            ],
            [
                'name' => 'Alias of the queue',
                'action' => 'Dequeue'
            ],
            [
                'name' => 'URL where an XML is to be returned',
                'action' => 'HTTP Action Request'
            ],
        ];
    }
}
