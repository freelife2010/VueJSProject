<?php

namespace App\Models;

use App\API\ApiClient\GuzzleClient;
use Auth;
use Former\Facades\Former;
use Illuminate\Database\Eloquent\SoftDeletes;

class DID extends BaseModel
{
    use GuzzleClient, SoftDeletes;

    protected $table = 'did';

    protected $fillable = [
        'did',
        'app_id',
        'account_id',
        'action_id',
        'owned_by',
        'rate_center',
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

    protected function appUser()
    {
        return $this->belongsTo('App\Models\AppUser', 'owned_by');
    }

    public function actionParameters() {
        return $this->hasMany('App\Models\DIDActionParameters', 'action_id', 'action_id')
                    ->whereDidId($this->id);
    }

    public function scopeAction($query) {
        return $query->join('did_action', 'action_id', '=', 'did_action.id');
    }

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
        }

        return false;
    }

    public function fillParams($request, $reserveId)
    {
        $params = $request->all();
        $user   = Auth::user();
        $params['reserve_id'] = $reserveId;
        $params['account_id'] = $user->id;
        $params['action_id'] = $params['action'];
        $storedDIDs = $request->session()->get('dids');
        $storedDID = $this->findReservedDID($request->did, $storedDIDs);
        if ($storedDID) {
            $params['did_type'] = $storedDID->category;
            $params['rate_center'] = $storedDID->RateCenter;
        }
        $this->fill($params);
    }

    public function createDIDParameters($request)
    {
        $parameters = $request->parameters;

        if ($parameters)
            foreach ($parameters as $paramId => $value) {
                $didParameter                  = new DIDActionParameters();
                $didParameter->did_id          = $this->id;
                $didParameter->action_id       = $request->action;
                $didParameter->parameter_id    = $paramId;
                $didParameter->parameter_value = $value;
                $didParameter->save();
            }
    }

}
