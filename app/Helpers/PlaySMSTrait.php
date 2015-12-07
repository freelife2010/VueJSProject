<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 07.12.15
 * Time: 17:40
 */

namespace App\Helpers;


use GuzzleHttp\Client;

trait PlaySMSTrait
{
    protected $smsToken = '3722dffa6cdb07b06d8e7916cf25b07a';
    protected $smsUser = 'admin';
    protected $smsResource = 'http://81.4.101.247/playsms/';

    protected function createSMSAccount($developer)
    {
        $params                  = [];
        $params['op']            = 'accountadd';
        $params['data_status']   = '4';
        $params['data_parent']   = 'premium';
        $params['data_username'] = $developer->email;
        $params['data_password'] = $developer['unhashed_pass'];
        $params['data_name']     = $developer->name;
        $params['data_email']    = $developer->email;

        $response = $this->makeSMSRequest($params);

    }

    protected function checkSMSInbox()
    {
        $params['op'] = 'ix';

        $response = json_decode($this->makeSMSRequest($params), true);

        return $response;
    }

    private function makeSMSRequest($params)
    {
        $params   = $this->getParamString($params);
        $client   = new Client([
            'base_uri' => $this->smsResource
        ]);
        $response = $client->request('GET', 'index.php', ['query' => $params]);

        return (string)$response->getBody();

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