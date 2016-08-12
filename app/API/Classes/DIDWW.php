<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 15.02.16
 * Time: 13:13
 */

namespace App\API\Classes;

use Didww\API2\ApiCredentials, Didww\API2\ApiClient as Client;
use Didww\API2\ServerObject;

class DIDWW extends ServerObject
{
    protected $username = 'akwong@intlcx.com';
    protected $password = 'VFJY81JNIIHQOESG34YWTGQI0PAXJ97IY';
    protected $testmode = false;

    public function __construct()
    {
        Client::setCredentials(new ApiCredentials($this->username,$this->password, $this->testmode));
    }

    public function getCountries($countryIso = null)
    {
        $method    = "getdidwwcountries";
        $params    = ['country_iso' => $countryIso];
        $countries = $this->invoke($method, $params);

        return $countries;
    }

    public function getRegions($params)
    {
        $method    = "getdidwwregions";
        $regions  = $this->invoke($method, $params);

        return $regions;
    }

    public function getCities($params)
    {
        $method    = "getdidwwcities";
        $regions  = $this->invoke($method, $params);

        return $regions;
    }

    public function getRates($params)
    {
        $method    = "getdidwwpstnrates";
        $regions  = $this->invoke($method, $params);

        return $regions;
    }

    private function invoke($method, $params)
    {
        return ServerObject::getClientInstance()->call($method, $params);
    }



}