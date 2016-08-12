<?php

namespace App\Models;


class Conference extends BaseModel
{
    protected $table = 'conference';
    protected $fillable = [
        'app_id',
        'host_pin',
        'guest_pin',
        'name',
        'greeting_prompt',
        'owner_user_id'
    ];

}
