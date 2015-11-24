<?php

namespace App\Models;

use App\API\ApiClient\GuzzleClient;
use App\Models\BaseModel;
use Auth;

class DID extends BaseModel
{
    use GuzzleClient;

    protected $table = 'did';

    protected $fillable = [
        'did',
        'app_id',
        'account_id',
        'reserve_id',
        'did_type',
        'state',
        'npa',
        'nxx',
        'city'
    ];

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
        $response = $this->sendPost('availabilitynpanxx', $data);

        return $this->makeResponse($response);
    }

    public function getAvailableNumbers($state, $rateCenter = '')
    {
        $data = $this->makeData(['state' => $state, 'ratecenter' => $rateCenter]);
        $response = $this->sendPost('searchdid', $data);

        return $this->makeResponse($response);
    }

    public function reserveDID($did)
    {
        $category = 'Landline';
        $data     = $this->makeData(['did' => $did, 'category' => $category]);
        $response = $this->sendPost('reserve', $data);

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

    public function getList($data, $labelField, $allOption = true)
    {
        $list = $allOption ? ['All'] : [];
        foreach ($data as $index => $entry) {
            $list[$entry->$labelField] = $entry->$labelField;
        }

        return $list;
    }

    public function findReservedDID($number, $storedDIDs)
    {
        foreach ($storedDIDs as $did) {
            if ($did->TN == $number)
                return $did;
            $a = 1;
        }

        return false;
    }

    public function fillParams($request, $reserveId)
    {
        $params = $request->all();
        $user   = Auth::user();
        $params['reserve_id'] = $reserveId;
        $params['account_id'] = $user->id;
        $storedDIDs = $request->session()->get('dids');
        $storedDID = $this->findReservedDID($request->did, $storedDIDs);
        if ($storedDID) {
            $params['did_type'] = $storedDID->category;
            $params['rate_center'] = $storedDID->RateCenter;
        }
        $this->fill($params);
    }
}