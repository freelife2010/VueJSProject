<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use App\Http\Controllers\Controller;
use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\App;
use App\Models\AppUser;
use App\Models\AppConfig;
use App\Models\AppRate;
use App\Models\Country;
use App\Models\MassCallConfig;
use Config;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Collection;

class RateController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/rates/country-list",
     *     summary="Get Rates For Country",
     *     tags={"rates"},
     *     @SWG\Response(response="200", description="Prefix"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getCountryList()
    {
        return $this->defaultResponse([
            'country_list' => $this->selectFromBillingDB('
                SELECT DISTINCT rate.country 
                  FROM rate ORDER BY rate.country ASC')
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/api/rates/destination",
     *     summary="Get Destination By Country",
     *     tags={"rates"},
     *     @SWG\Parameter(
     *         description="country",
     *         in="query",
     *         name="country",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Response(response="200", description="Prefix"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getDestination()
    {
        return $this->defaultResponse([
            'destination_list' => $this->selectFromBillingDB('
                SELECT DISTINCT rate.code_name AS destination 
                  FROM rate 
                    WHERE rate.country = ?
                ORDER BY rate.code_name ASC', [$this->request->country])
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/api/rates/rates-for-country",
     *     summary="Get Rates For Country",
     *     tags={"rates"},
     *     @SWG\Parameter(
     *         description="app_id",
     *         in="query",
     *         name="app_id",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         description="country",
     *         in="query",
     *         name="country",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Response(response="200", description="Prefix"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getRatesForCountry()
    {
        $app = App::find($this->request->app_id);
        if (!$app) {
            return $this->response->errorInternal('APP does not exist');
        }
        $country = $this->request->country;
        $appRate = new AppRate($app);
        $rates = new Collection($appRate->getGlobalRates($withAppRate = true, $whereCondition = "AND rate.country = '{$country}'"));

        return $this->defaultResponse(['rates' => $rates]);
    }


    /**
     * @SWG\Get(
     *     path="/api/rates/rates-for-destination",
     *     summary="Get Rates For Destination",
     *     tags={"rates"},
     *     @SWG\Parameter(
     *         description="app_id",
     *         in="query",
     *         name="app_id",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         description="destination",
     *         in="query",
     *         name="destination",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Response(response="200", description="Prefix"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getRatesForDestination()
    {
        $app = App::find($this->request->app_id);
        if (!$app) {
            return $this->response->errorInternal('APP does not exist');
        }
        $destination = $this->request->destination;
        $appRate = new AppRate($app);
        $rates = new Collection($appRate->getGlobalRates($withAppRate = true, $whereCondition = "AND rate.code_name = '{$destination}'"));

        return $this->defaultResponse(['rates' => $rates]);
    }

    /**
     * @SWG\Get(
     *     path="/api/rates/rates-for-code",
     *     summary="Get Rates For Code",
     *     tags={"rates"},
     *     @SWG\Parameter(
     *         description="app_id",
     *         in="query",
     *         name="app_id",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         description="code",
     *         in="query",
     *         name="code",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Response(response="200", description="Prefix"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getRatesForCode()
    {
        $app = App::find($this->request->app_id);
        if (!$app) {
            return $this->response->errorInternal('APP does not exist');
        }
        $code = $this->request->code;
        $appRate = new AppRate($app);
        $rates = new Collection($appRate->getGlobalRates($withAppRate = true, $whereCondition = "AND rate.code = '{$code}'"));

        return $this->defaultResponse(['rates' => $rates]);
    }

    /**
     * @SWG\Post(
     *     path="/api/rates/update-rate",
     *     summary="Update Rate",
     *     tags={"rates"},
     *      @SWG\Parameter(
     *         description="App Id",
     *         in="formData",
     *         name="app_id",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="Code",
     *         in="formData",
     *         name="code",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="Rate",
     *         in="formData",
     *         name="rate",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postUpdateRate()
    {
        $app = App::find($this->request->app_id);
        if (!$app) {
            return $this->response->errorInternal('APP does not exist');
        }

        $appRate = new AppRate($app);
        if (!$appRate)
            return $this->response->errorInternal('App Rate was not found');

        $rate = $this->getFluentBilling('rate')
            ->where('code', $this->request->code)
            ->first();
        if (!$rate)
            return $this->response->errorInternal('Rate was not found');

        if ($rate->rate_table_id == 10) { // create
            $appRate->createRate($rate->rate_id, (double) $this->request->rate);
        } else { // update
            $appRate->saveRate($rate->rate_id, (double) $this->request->rate);
        }
        return $this->defaultResponse(['success' => true]);
    }

}
