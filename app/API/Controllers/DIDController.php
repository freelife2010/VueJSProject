<?php

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\DID;
use Config;
use DB;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

class DIDController extends Controller
{
    use Helpers, APIHelperTrait;

    public function __construct()
    {
        $this->initAPI();
        $this->scopes('pbx');
    }

    public function getActionsParameters()
    {
        $selectFields = [
            'dap.id as parameter_id',
            'dap.name as parameter_name',
            'da.id as action_id',
            'da.name as action_name'
        ];
        $params = DB::table('did_action_parameters as dap')
                    ->select($selectFields)
                    ->leftJoin('did_action as da', 'dap.action_id', '=', 'da.id')
                    ->get();

        return $params;
    }

    public function postAvailabilitystate(Request $request)
    {

        $did = new DID();

        return $did->getStates();
    }

    public function postAvailabilitynpanxx(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'state' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $did = new DID();
        return $did->getNPA($request->state);
    }

    public function postSearchdid(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'state' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $state       = $request->state;
        $rateCenter  = isset($request->rate_center) ? $request->rate_center : '';
        $did         = new DID();

        $numbers     = $did->getAvailableNumbers($state, $rateCenter);
        if (!empty($numbers->Numbers)) {
            $numbers = $numbers->Numbers;
            $request->session()->put('dids', ($numbers));
        } else $numbers = ['Not found'];

        return $numbers;
    }

    public function postReserve(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'did'        => 'required',
            'action_id'  => 'required',
            'app_id'     => 'required',
            'owned_by'   => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        return $this->buyDID($request);
    }

    protected function buyDID($request)
    {
        $did    = new DID();
        if ($request->parameters)
            $request->parameters = (array)json_decode($request->parameters);
        $response = $did->reserveDID($request->did);
        if (isset($response->reserveId)) {
            $did->reserve_id = $response->reserveId;
            $this->fillDIDParams($did, $request);
            if ($did->save()) {
                $request->action = $request->action_id;
                $did->createDIDParameters($request);
            }
        }

        return $this->response->array((array) $response);
    }

    protected function fillDIDParams($did, $request)
    {
        $params     = $request->all();
        $storedDIDs = $request->session()->get('dids');
        $storedDID  = $did->findReservedDID($request->did, $storedDIDs);
        if ($storedDID) {
            $params['did_type'] = $storedDID->category;
            $params['rate_center'] = $storedDID->RateCenter;
        }
        $did->fill($params);
    }
}
