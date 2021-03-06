<?php

namespace App\Models;


class Queue extends BaseModel
{
    protected $table = 'queue';

    protected $fillable = [
        'app_id',
        'queue_name',
        'client_waiting_prompt',
        'agent_waiting_prompt',
        'client_waiting_audio',
        'agent_waiting_audio',
        'created_by'
    ];

}
