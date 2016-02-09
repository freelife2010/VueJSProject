<?php

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\App;
use App\Models\DID;
use Config;
use DB;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

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
        $this->setValidator([
            'did'        => 'required',
            'action_id'  => 'required',
            'owned_by'   => 'required|exists:users,id,deleted_at,NULL'
        ]);

        $request->app_id = $this->getAPPIdByAuthHeader();

        return $this->buyDID($request);
    }

    protected function buyDID($request)
    {
        $did    = new DID();
        if ($request->parameters)
            $request->parameters = (array)json_decode($request->parameters);
        $response = (array) $did->buyDID($request->did);
        if (!empty($response['Order NO'])) {
            $did->reserve_id = $response['Order NO'];
            $this->fillDIDParams($did, $request);
            $did->createBillingDBData();
            if ($did->save()) {
                $request->action = $request->action_id;
                $did->createDIDParameters($request);
            }
        }

        return $this->makeResponse((array) $response);
    }

    protected function fillDIDParams($did, $request)
    {
        $params               = $request->input();
        $params['app_id']     = $request->app_id;
        $app                  = App::find($request->app_id);
        $params['account_id'] = $app->account_id;
        $storedDIDs           = $request->session()->get('dids') ?: [];
        $storedDID            = $did->findReservedDID($request->did, $storedDIDs);
        if ($storedDID) {
            $params['did_type']    = $storedDID->category;
            $params['rate_center'] = $storedDID->RateCenter;
        }
        $did->fill($params);
    }

    public function postSetAction()
    {
        $this->setValidator([
            'did'        => 'required',
            'action_id'  => 'required',
            'owned_by'   => 'required'
        ]);
    }
}
