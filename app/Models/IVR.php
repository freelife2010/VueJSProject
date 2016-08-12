<?php

namespace App\Models;

class IVR extends BaseModel
{
    protected $table = 'ivr';

    protected $fillable = [
        'name',
        'alias',
        'parameter',
        'account_id'
    ];
}
