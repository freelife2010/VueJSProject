<?php

namespace App\Models;

use App\API\ApiClient\GuzzleClient;
use App\Models\BaseModel;

class DID extends BaseModel
{
    use GuzzleClient;

    protected $table = 'did';


    protected $credentials = [
        'accountno' => '',
        'token' => ''
    ];


    function __construct()
    {
        $config = [
            'base_uri' => 'https://customer.vitcom.net/api/did/',
            'timeout'  => 2.5
        ];
        $this->createHttpClient($config);
        $this->setCredentials();
    }

    protected function setCredentials()
    {
        $this->credentials['accountno'] = env('DID_API_ID', 2334425286);
        $this->credentials['token'] = env('DID_API_TOKEN', 'o088c565712a945f5b45fe26bdab5d72');
    }

    public function getStates()
    {
        $data = $this->makeData();
        $response = $this->sendPost('availabilitystate', $data);

        return $this->makeResponse($response);
    }

    public function getNPA($state)
    {
        $data = $this->makeData(['state' => $state]);
        $response = $this->sendPost('availabilitynpa', $data);

        return $this->makeResponse($response);
    }

    protected function makeData($params = [])
    {
        $params = array_merge($this->credentials, $params);

        $data = [
            'form_params' => $params
        ];

        return $data;
    }

    protected function makeResponse($response, $dataField = 'data')
    {
        $code     = $response->getStatusCode();
        $response = json_decode($response->getBody());

        return (isset($response->error)
            or !isset($response->$dataField)
            or $code != 200) ? false :
                $dataField ?
                    $response->$dataField :
                    $response;
    }
}
