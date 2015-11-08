<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name')->unique();
            $table->string('email');
            $table->string('password');
            $table->integer('account_id', false, true);
            $table->boolean('presence')->default(1);
            $table->string('token')->default('');

            $this->createBooleanFields($table);

            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id', 'accound_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('app');
    }

    protected function createBooleanFields($table)
    {
        $fields = [
            'allow_block_get',
            'allow_block_post',
            'allow_block_put',
            'allow_block_delete',
            'allow_call_queues_get',
            'allow_call_queues_post',
            'allow_call_queues_put',
            'allow_call_queues_delete',
            'allow_friend_get',
            'allow_friend_post',
            'allow_friend_put',
            'allow_friend_delete',
            'allow_group_get',
            'allow_group_post',
            'allow_group_put',
            'allow_group_delete',
            'allow_message_get',
            'allow_message_post',
            'allow_message_put',
            'allow_message_delete',
            'allow_outbound_call_get',
            'allow_outbound_call_post',
            'allow_outbound_call_put',
            'allow_outbound_call_delete',
            'allow_phone_number_get',
            'allow_phone_number_post',
            'allow_phone_number_put',
            'allow_phone_number_delete',
            'allow_sip_get',
            'allow_sip_post',
            'allow_sip_put',
            'allow_sip_delete',
            'allow_storage_get',
            'allow_storage_post',
            'allow_storage_put',
            'allow_storage_delete',
            'allow_user_get',
            'allow_user_post',
            'allow_user_put',
            'allow_user_delete',
            'allow_voip_get',
            'allow_voip_post',
            'allow_voip_put',
            'allow_voip_delete'
        ];

        foreach ($fields as $field) {
            $table->boolean($field)->default(1);
        }
    }
}
