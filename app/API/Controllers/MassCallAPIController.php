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
use App\Models\MassCallConfig;
use Config;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Webpatser\Uuid\Uuid;

class MassCallAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Post(
     *     path="/api/mass-call/enable",
     *     summary="Enable Mass Call",
     *     tags={"mass-call"},
     *      @SWG\Parameter(
     *         description="App ID",
     *         in="formData",
     *         name="app_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Prefix"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postEnable()
    {
        if ($app = App::find($this->request->app_id)) {
            $massCallConfig = MassCallConfig::select('id')->where('app_id', '=', $app->id)->where('enabled', '=', 1)->first();
            if (isset($massCallConfig->id)) {
                $this->response->errorInternal('Mass Call is Already Enabled for this APP');
            }

            $app = APP::find($app->id);
            $appTechPrefix = $app->tech_prefix;
            $developerId = $app->account_id;
            $developerEmail = $app->email;
            $developerClientId = $this->getFluentBilling('client')
                ->where('name', '=', $developerEmail)
                ->first()
                ->client_id;

            $configValues = AppConfig::all()->lists('config_value', 'config_key');
            $icxCCRateTableId = isset($configValues['icx_cc_rate_table']) ? $configValues['icx_cc_rate_table'] : 0;
            $ccGatewayIp = isset($configValues['cc_gateway_ip']) ? $configValues['cc_gateway_ip'] : '127.0.0.1';
            $routeStrategyId = isset($configValues['route_strategy_id']) ? $configValues['route_strategy_id'] : 1;

            $resourceId   = $this->insertGetIdToBillingDB("
                                  insert into resource
                                  (client_id, alias, egress, rate_table_id)
                                  values (?,?,'t',?) RETURNING resource_id",
                [$developerClientId, "{$appTechPrefix}_CC_term", $icxCCRateTableId], 'resource_id');

            $resourceIpId = $this->insertGetIdToBillingDB("
                                  insert into resource_ip
                                  ( resource_id, ip)
                                  values (?,?) RETURNING resource_id",
                [$resourceId, $ccGatewayIp], 'resource_id');
            $rateTableId = $this->insertGetIdToBillingDB("
                                    insert into rate_table
                                    (name, currency_id)
                                    values(?, '1') RETURNING rate_table_id",
                ["{$appTechPrefix}_CC"], 'rate_table_id');

            $appRouteStrategyId = $this->insertGetIdToBillingDB("
                                        insert into route_strategy ( name ) 
                                        values (?) RETURNING route_strategy_id",
                ["{$appTechPrefix}_CC"], 'route_strategy_id');

            $appProductId = $this->insertGetIdToBillingDB("
                                    insert into product ( name ) 
                                    values (?) RETURNING product_id",
                ["{$appTechPrefix}_CC"], 'product_id');

            $appProductItemId = $this->insertGetIdToBillingDB("
                                insert into product_items ( product_id, code_name ) 
                                values (?, ?) RETURNING item_id",
                [$appProductId, null], 'item_id');

            $appProductItemResourceId = $this->insertGetIdToBillingDB("
                            insert into product_items_resource ( item_id, resource_id ) 
                            values (?, ?) RETURNING id",
                [$appProductItemId, $resourceId], 'id');

            $appRouteId = $this->insertGetIdToBillingDB("
                        insert into route ( static_route_id, route_type,  route_strategy_id) 
                        values (?, '2', ?) RETURNING route_id",
                [$appProductId, $appRouteStrategyId], 'route_id');


            $appUsers = AppUser::where('app_id', '=', $app->id)->get();
            foreach ($appUsers as $u) {
                $prefix = str_replace('-', '', $u->getUserAlias());
                $resourcePrefixId = $this->insertGetIdToBillingDB("
                        insert into resource_prefix ( resource_id, tech_prefix , rate_table_id, route_strategy_id ) 
                        values (?, ?, ?, ?) RETURNING id",
                    [$resourceId, "#{$prefix}", $rateTableId, $appRouteStrategyId], 'id');
            }

            MassCallConfig::insert([
                'app_id' => $app->id,
                'enabled' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->defaultResponse(['response' => 'Mass Call is Successfully Enabled']);
        } else {
            $this->response->errorInternal('The APP was not found');
        }
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/generate-session",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="ani_list",
     *         in="query",
     *         name="ani_list",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="dnis_list",
     *         in="query",
     *         name="dnis_list",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="allowed_time",
     *         in="query",
     *         name="allowed_time",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="app_user_id",
     *         in="query",
     *         name="user_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Session key"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getGenerateSession()
    {
        $appUser = AppUser::find($this->request->user_id);
        if (!$appUser) {
            $this->response->errorInternal('The App User was not found');
        }
        $massCallConfig = MassCallConfig::select('id')->where('app_id', '=', $appUser->app_id)->where('enabled', '=', 1)->first();
        if (!isset($massCallConfig->id)) {
            $this->response->errorInternal('Mass Call is not Enabled for this APP');
        }

        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');

        $prefix = '#' . str_replace('-', '', $appUser->getUserAlias());
        $parameters = [
            'ani_list' => $this->request->ani_list,
            'dnis_list' => $this->request->dnis_list,
            'prefix' => $prefix,
            'action' => 'park',
            'allowed_time' => $this->request->allowed_time,
            'smoothing' => '0.5'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/CreateSession');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/suspend-session",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getSuspendSession()
    {
        session_start();
        die(var_dump($_SESSION));
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/SuspendSession');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/resume-session",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getResumeSession()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/ResumeSession');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/stop-session",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getStopSession()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/StopSession');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/session-stat",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getSessionStat()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetSessionStat');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/dialed-numbers",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getDialedNumbers()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetDialedNumbers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/successful-numbers",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getSuccessfulNumbers()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetSuccessfulNumbers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/failed-numbers",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getFailedNumbers()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetFailedNumbers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/invalid-numbers",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getInvalidNumbers()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetInvalidNumbers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/busy-numbers",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getBusyNumbers()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetBusyNumbers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/unanswered-numbers",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getUnansweredNumbers()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetUnansweredNumbers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

    /**
     * @SWG\Get(
     *     path="/api/mass-call/dial-history",
     *     summary="Generate Session",
     *     tags={"mass-call"},
     *     @SWG\Parameter(
     *         description="session_id",
     *         in="query",
     *         name="session_id",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="dnis",
     *         in="query",
     *         name="dnis",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getDialHistory()
    {
        $host = \Config::get('app.api_masscall_host');
        $port = \Config::get('app.api_masscall_port');
        $parameters = [
            'session_id' => $this->request->session_id,
            'dnis' => $this->request->dnis,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/GetDialHistory');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->defaultResponse(['response' => $response]);
    }

}
