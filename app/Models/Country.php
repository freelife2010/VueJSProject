<?php

namespace App\Models;


class Country extends BaseModel
{
    const COUNTRY_US_ID = 1;

    protected $table = 'countries';

    public static function getCountryList()
    {
        $countries = self::where('name', '<>', 'undefined')->lists('name', 'id')->all();

        asort($countries);

        return $countries;
    }
}
