<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 07.12.15
 * Time: 17:40
 */

namespace App\Helpers;


use Cache;
use GuzzleHttp\Client;

/**
 * Class PlaySMSTrait to work with SMS API
 * @package App\Helpers
 */
trait PlaySMSTrait
{
    protected $smsToken = '3722dffa6cdb07b06d8e7916cf25b07a';
    protected $smsUser = 'admin';
    protected $smsResource = 'http://81.4.101.247/playsms/';

    protected function createSMSAccount($developer)
    {
        $pass                    = Cache::pull('playSMSPass', '');
        $params                  = [];
        $params['op']            = 'accountadd';
        $params['data_status']   = '4';
        $params['data_parent']   = 'premium';
        $params['data_username'] = $developer->email;
        $params['data_password'] = $pass;
        $params['data_name']     = $developer->name;
        $params['data_email']    = $developer->email;

        return $this->makeSMSRequest($params);

    }

    protected function addCredit($username, $amount)
    {
        $params                  = [];
        $params['op']            = 'creditadd';
        $params['data_username'] = $username;
        $params['data_amount']   = $amount;

        return $this->makeSMSRequest($params);
    }

    protected function sendSMS($number, $message)
    {
        $params        = [];
        $params['op']  = 'pv';
        $params['to']  = $number;
        $params['msg'] = $message;

        $request = json_decode($this->makeSMSRequest($params));

        return @$request->error_string;
    }

    protected function checkSMSInbox()
    {
        $params['op'] = 'ix';

        $response = json_decode($this->makeSMSRequest($params), true);

        return $response;
    }

    protected function getSMSLog($params = [])
    {
        $params['op'] = 'ds';

        return json_decode($this->makeSMSRequest($params), true);
    }

    private function makeSMSRequest($params)
    {
        $params   = $this->getParamString($params);
        $client   = new Client([
            'base_uri' => $this->smsResource,
            'timeout'  => 3
        ]);
        try {
            $response = $client->request('GET', 'index.php', ['query' => $params]);
            $response = (string) $response->getBody();
        } catch (\Exception $e) {
            $response = '';
        }

        return (string) $response;

    }

    private function getParamString($params)
    {
        $credentials = [
            'app' => 'ws',
            'u'   => $this->smsUser,
            'h'   => $this->smsToken
        ];
        $params      = array_merge($credentials, $params);

        return $params;
    }
}