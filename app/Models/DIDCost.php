<?php

namespace App\Models;


class DIDCost extends BaseModel
{
    protected $table = 'costs_did';
    protected $fillable = [
        'state',
        'rate_center',
        'value'
    ];

}
