<?php

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\API\Classes\DIDWW;
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

    /**
     * @SWG\Get(
     *     path="/api/did/actions-parameters",
     *     summary="DID Actions-parameters list",
     *     tags={"did"},
     *     @SWG\Response(response="200", description="Action list"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
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

    /**
     * @SWG\Post(
     *     path="/api/did/availabilitystate",
     *     summary="DID availability state",
     *     tags={"did"},
     *     @SWG\Response(response="200", description="DID States"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postAvailabilitystate()
    {

        $did = new DID();

        return $did->getStates();
    }


    /**
     * @SWG\Post(
     *     path="/api/did/availabilitynpanxx",
     *     summary="DID availability NPA",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="State",
     *         name="state",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="State NPA"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="404", description="State not found"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function postAvailabilitynpanxx(Request $request)
    {
        $this->setValidator([
            'state' => 'required|string'
        ]);

        $did = new DID();
        $states = $did->getStates();
        if ($states) $states = array_flip($states);

        return isset($states[$request->state]) ?
            $did->getNPA($request->state) :
            $this->response->errorNotFound('State not found');

        return $did->getNPA($request->state);
    }

    /**
     * @SWG\Post(
     *     path="/api/did/searchdid",
     *     summary="Search DIDs by state",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="State",
     *         name="state",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Available DIDs"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
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

    /**
     * @SWG\Post(
     *     path="/api/did/reserve",
     *     summary="Buy DID",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="DID",
     *         name="did",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="DID Action ID",
     *         name="action_id",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="DID User ID",
     *         name="owned_by",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
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

    /**
     * @SWG\Post(
     *     path="/api/did/set-action",
     *     summary="Set DID action",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="DID",
     *         name="did",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="DID Action ID",
     *         name="action_id",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Parameters (JSON)",
     *         name="parameters",
     *         in="formData",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Updated DID"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postSetAction()
    {
        $this->setValidator([
            'did'        => 'required|exists:did,did,deleted_at,NULL',
            'action_id'  => 'required',
            'parameters' => 'sometimes|required|json'
        ]);

        $result = ['error' => 'Failed to change DID action'];
        $did    = DID::whereDid($this->request->did)->first();
        if ($this->request->parameters)
            $this->request->parameters = (array)json_decode($this->request->parameters);
        $did->action_id        = $this->request->action_id;
        $this->request->action = $did->action_id;
        if ($did->save()
            and $did->deleteDIDParameters()
            and $did->createDIDParameters($this->request)) {
            $did = DID::find($did->id);
            $did->actionParameters;
            $result = ['entities' => $did];
        }

        return $this->defaultResponse($result);

    }

    /**
     * @SWG\Get(
     *     path="/api/did/available-countries",
     *     summary="Get DID available countries",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="Country ISO",
     *         name="country_iso",
     *         in="query",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="DID Countries"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param DIDWW $handler
     * @return bool|mixed
     */
    public function getAvailableCountries(DIDWW $handler)
    {

        return $handler->getCountries($this->request->country_iso);
    }

    /**
     * @SWG\Get(
     *     path="/api/did/available-regions",
     *     summary="Get DID available regions",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="Country ISO",
     *         name="country_iso",
     *         in="query",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="City prefix",
     *         name="city_prefix",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="DID Regions"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param DIDWW $handler
     * @return bool|mixed
     */
    public function getAvailableRegions(DIDWW $handler)
    {

        return $handler->getRegions($this->request->except('access_token'));
    }

    /**
     * @SWG\Get(
     *     path="/api/did/available-cities",
     *     summary="Get DID available cities",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="Country ISO",
     *         name="country_iso",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="City ID",
     *         name="city_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="DID Cities"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param DIDWW $handler
     * @return bool|mixed
     */
    public function getAvailableCities(DIDWW $handler)
    {
        $this->setValidator([
            'country_iso' => 'required'
        ]);

        return $handler->getCities($this->request->except('access_token'));
    }

    /**
     * @SWG\Get(
     *     path="/api/did/rates",
     *     summary="Get DID rates",
     *     tags={"did"},
     *     @SWG\Parameter(
     *         description="Country ISO",
     *         name="country_iso",
     *         in="query",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="DID Rates"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param DIDWW $handler
     * @return bool|mixed
     */
    public function getRates(DIDWW $handler)
    {
        return $handler->getRates($this->request->except('access_token'));
    }
}
