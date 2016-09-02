<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Helpers\PlaySMSTrait;
use App\Http\Requests\AppRequest;
use App\Http\Requests\DeleteRequest;
use App\Jobs\StoreAPPToBillingDB;
use App\Jobs\StoreAPPToChatServer;
use App\Models\App;
use App\Http\Requests;
use App\Models\AppUser;
use App\Models\AppConfig;
use App\Models\GoogleApiData;
use App\Models\MassCallConfig;
use Illuminate\Http\Request;
use URL;
use Yajra\Datatables\Datatables;
use App\Helpers\Misc;

class AppConfigController extends AppBaseController
{
    use BillingTrait, PlaySMSTrait;

    /**
     * AppController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
//        $this->middleware('auth');
//        $this->middleware('csrf');
//        $this->middleware('role:admin', [
//            'except' => [
//                'getMassCall',
//                'getData',
//                'getEnableMassCall',
//                'getGoogleApi',
//                'postQwe',
//                'postSaveGoogleApiData'
//            ]
//        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title    = 'Config Page';
        $subtitle = 'Config Page';
        $configValues = AppConfig::all()->lists('config_value', 'config_key');

        return view('appConfig.index', compact('title', 'subtitle', 'configValues'));
    }

    public function postSaveConfig(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            $config = AppConfig::select('id')->where('config_key', '=', $key)->first();
            if (isset($config->id))
                AppConfig::where('id', '=', $config->id)->update(['config_value' => $value]);
            else
                AppConfig::insert(['config_key' => $key, 'config_value' => $value, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        }

        return $this->getResult(false, 'Config Values Are Successfully Saved');
    }

    public function getMassCall()
    {
        $title    = 'APP List';
        $subtitle = 'Mass Call API';

        return view('appConfig.mass-call', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $apps = App::getApps([
            'app.id',
            'app.tech_prefix',
            'app.name',
            'app.presence',
            'mass_call_config.enabled'
        ])->leftJoin('mass_call_config', 'mass_call_config.app_id', '=', 'app.id');

        return Datatables::of($apps)
            ->add_column('users', function ($app) {
                $users = $app->users;

                return count($users->all());
            })
            ->add_column('actions', function ($app) {
                $disabled = isset($app->enabled) && $app->enabled == 1 ? 'disabled' : '';

                return '<a href="' . URL::to('app-config/enable-mass-call') . '/' . $app->id . '"
                           data-target="#myModal"
                           data-toggle="modal"
                           title="Enable"
                           class="btn btn-danger btn-sm ' . $disabled . '" >
                            <span class="fa fa-check"></span></a>';
            })
            ->setRowId('id')
            ->make(true);
    }

    public function getEnableMassCall($appId)
    {
        try {
            $massCallConfig = MassCallConfig::select('id')->where('app_id', '=', $appId)->where('enabled', '=', 1)->first();
            if (isset($massCallConfig->id)) {
                return $this->getResult(false, 'Mass Call is Already Enabled for this APP');
            }

            $app = APP::find($appId);
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

            
            $appUsers = AppUser::where('app_id', '=', $appId)->get();
            foreach ($appUsers as $u) {
                $prefix = $u->getUserAlias();
                $resourcePrefixId = $this->insertGetIdToBillingDB("
                        insert into resource_prefix ( resource_id, tech_prefix , rate_table_id, route_strategy_id ) 
                        values (?, ?, ?, ?) RETURNING id",
                    [$resourceId, "#{$prefix}", $rateTableId, $appRouteStrategyId], 'id');
            }

            MassCallConfig::insert([
                'app_id' => $appId,
                'enabled' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return 'Mass Call is Successfully Enabled';
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function getGoogleApi()
    {
        $title    = 'Google API Page';
        $subtitle = 'Manage Google API';

        $userId = \Auth::user()->id;
        $googleApiData = GoogleApiData::where('user_id', '=', $userId)->first();

        return view('appConfig.google-api', compact('title', 'subtitle', 'googleApiData', 'userId'));
    }

    public function postSaveGoogleApiData(Request $request)
    {
        $userId = $request->get('user_id');
        $googleApiData = GoogleApiData::select('id')->where('user_id', '=', $userId)->first();

        $executeArray = [
            'project_id' => $request->get('project_id'),
            'private_key_id' => $request->get('private_key_id'),
            'private_key' => $request->get('private_key'),
            'client_email' => $request->get('client_email'),
            'client_id' => $request->get('client_id'),
            'client_x509_cert_url' => $request->get('client_x509_cert_url')
        ];
        if (isset($googleApiData->id)) {
            GoogleApiData::where('user_id', '=', $userId)
                ->update($executeArray);
        } else {
            $executeArray['user_id'] = $userId;
            $executeArray['created_at'] = date('Y-m-d H:i:s');
            $executeArray['updated_at'] = date('Y-m-d H:i:s');

            GoogleApiData::insert($executeArray);
        }

        return $this->getResult(false, 'Google API Config Values Are Successfully Saved');
    }
}
