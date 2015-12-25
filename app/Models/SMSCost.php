<?php

namespace App\Models;


class SMSCost extends BaseModel
{
    protected $table = 'costs_sms';
    protected $fillable = [
        'country_id',
        'cents_value'
    ];

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public static function createCost($params)
    {
        $result = false;
        if (isset($params['countries'])) {
            foreach ($params['countries'] as $countryId) {
                $smsCost = SMSCost::whereCountryId($countryId)->first();
                $smsCost = $smsCost ?: new SMSCost();
                $smsCost->country_id = $countryId;
                $smsCost->cents_value = $params['cents_value'];
                $result = $smsCost->save();
            }
        }

        return $result;
    }
}
