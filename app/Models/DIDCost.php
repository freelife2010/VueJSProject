<?php

namespace App\Models;


class DIDCost extends BaseModel
{
    protected function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    protected $table = 'costs_did';
    protected $fillable = [
        'country_id',
        'state',
        'rate_center',
        'value',
        'one_time_value',
        'per_month_value'
    ];

}
