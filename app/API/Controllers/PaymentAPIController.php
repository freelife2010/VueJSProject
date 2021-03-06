<?php

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

class PaymentAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    private $userIdValidationRule = 'required|exists:users,id,deleted_at,NULL';


    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/balance",
     *     summary="Return user balance",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         description="APP User ID",
     *         name="userid",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Balance"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getBalance(Request $request)
    {
        $this->setValidator([
            'userid' => $this->userIdValidationRule
        ]);
        $user = AppUser::find($request->userid);

        $balance = 0;

        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());
        if ($clientId)
            $balance = $this->getClientBalanceFromBillingDB($clientId, 'ingress_balance');

        return $this->response->array(['balance' => $balance]);
    }

    /**
     * @SWG\Post(
     *     path="/api/addCredit",
     *     summary="Add user's credit",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         description="APP User ID",
     *         name="userid",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Amount",
     *         name="amount",
     *         in="formData",
     *         required=true,
     *         type="number"
     *     ),
     *     @SWG\Parameter(
     *         description="Remark",
     *         name="remark",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function postAddCredit(Request $request)
    {
        $this->setValidator([
            'userid' => $this->userIdValidationRule,
            'amount' => 'required|numeric'
        ]);

        $user = AppUser::find($request->userid);

        $response = ['result' => 'Failed'];

        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());
        if ($clientId) {
            $this->storeClientPaymentInBillingDB($clientId, $request->amount, $request->remark);
            $response = ['result' => 'ok'];
        }

        return $this->response->array($response);

    }

    /**
     * @SWG\Get(
     *     path="/api/creditHistory",
     *     summary="Get credit history",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         description="APP User ID",
     *         name="userid",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Credit history"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getCreditHistory(Request $request)
    {
        $this->setValidator([
            'userid' => $this->userIdValidationRule
        ]);
        $user = AppUser::find($request->userid);

        $response = [];

        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());
        if ($clientId)
            $response = $this->getClientPaymentsFromBillingDB($clientId);

        return $this->response->array($response);
    }

    /**
     * @SWG\Get(
     *     path="/api/getAllowedCountry",
     *     summary="Return allowed country",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         description="APP User ID",
     *         name="userid",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Allowed country"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getAllowedCountry(Request $request)
    {
        $this->setValidator([
            'userid' => $this->userIdValidationRule
        ]);
        $user = AppUser::find($request->userid);

        $response = [];
        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());
        if ($clientId) {
            $rateTableId = $this->getRateTableIdByClientId($clientId);
            if ($rateTableId)
                $response = $this->queryAllowedCountries($rateTableId);
        }
	
	// $response = "select country from rate where rate_table_id = ".$rateTableId." AND country IS NOT NULL AND ((now() BETWEEN effective_date AND end_date) OR end_date IS NULL )";

        return $this->response->array($response);
    }

    protected function queryAllowedCountries($rateTableId)
    {
        $result = [];
        $data   = $this->selectFromBillingDB('
                    select country from rate where rate_table_id = ?
                    AND country IS NOT NULL
                    AND ((now() BETWEEN effective_date AND end_date) OR end_date IS NULL )', [$rateTableId]);
        if ($data) {
            foreach ($data as $entry) {
                $result[] = $entry->country;
            }
        }

        return $result;
    }

    /**
     * @SWG\Get(
     *     path="/api/getRates",
     *     summary="Return rates for exact country",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         description="APP User ID",
     *         name="userid",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Country",
     *         name="country",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Rates"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="404", description="Not found"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getRates()
    {
        $request = $this->request;
        $this->setValidator([
            'userid'  => $this->userIdValidationRule,
            'country' => 'required'
        ]);

        $user = AppUser::find($request->userid);

        $response = [];
        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());

        if ($clientId) {
            $rateTableId = $this->getRateTableIdByClientId($clientId);
            if ($rateTableId)
                $response = $this->queryRates($rateTableId, $request->country);
        }

        return $response ? $this->response->array($response) : $this->response->errorNotFound();
    }

    protected function queryRates($rateTableId, $country)
    {
        return $this->selectFromBillingDB('
                    select code_name, rate from rate where rate_table_id = ?
                    AND ((now() BETWEEN effective_date AND end_date) OR end_date IS NULL )
                    AND country = ?', [$rateTableId, $country]);

    }

    /**
     * @SWG\Get(
     *     path="/api/getRate",
     *     summary="Return rates for exact number",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         description="APP User ID",
     *         name="userid",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Number",
     *         name="number",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Rates"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getRate()
    {
        $request = $this->request;
        $this->setValidator([
            'userid' => $this->userIdValidationRule,
            'number' => 'required|integer'
        ]);

        $user = AppUser::find($request->userid);

        $response = [];
        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());

        if ($clientId) {
            $rateTableId = $this->getRateTableIdByClientId($clientId);
            if ($rateTableId)
                $response = $this->queryRateByNumber($rateTableId, $request->number);
        }
	
	// $response = "select rate from rate where rate_table_id = ".$rateTableId." AND ((now() BETWEEN effective_date AND end_date) OR end_date IS NULL ) AND code @> ".$request->number." ORDER BY length(code::text) desc LIMIT 1";

	
        return $this->response->array($response);
    }

    protected function queryRateByNumber($rateTableId, $number)
    {
        return $this->selectFromBillingDB('
                    select rate from rate where rate_table_id = ?
                    AND ((now() BETWEEN effective_date AND end_date) OR end_date IS NULL )
                    AND code @> ? ORDER BY length(code::text) desc LIMIT 1', [$rateTableId, $number]);

    }

}
