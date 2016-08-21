<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use App\Jobs\Job;
use App\Models\MassCallConfig;
use Illuminate\Contracts\Bus\SelfHandling;

class StoreAPPUserToBillingDB extends Job implements SelfHandling
{
    use BillingTrait;

    protected $user;
    protected $app;
    protected $clientNamePostfix;

    /**
     * Create a new job instance.
     *
     * @param $user
     * @param $app
     */
    public function __construct($user, $app)
    {
        $this->user = $user;
        $this->app  = $app;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // THESE TESTERS ARE KILLING ME.
        $currencyId = $this->getCurrencyIdFromBillingDB();
        $clientName = $this->user->getUserAlias();
        $newClientName = $clientName;
        $clientId   = $this->insertGetIdToBillingDB("
                              insert into client
                              (name,currency_id,unlimited_credit,mode,enough_balance)
                              values (?,?,'t',1,'t') RETURNING client_id",
            [$clientName, $currencyId], 'client_id');
        $this->insertToBillingDB("
                  insert into c4_client_balance (client_id,balance,ingress_balance)
                  values (?,0,0) ", [$clientId]);

        $resourceIdDID = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'f','t','t',2) RETURNING resource_id",
            ["{$clientName}_DID", $clientId], 'resource_id');

        // should be $clientId, but for some reason customers asked to hardcode 429 is client_id..
        $resourceIdP2P = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'f','t','t',2) RETURNING resource_id",
            ["{$clientName}_P2P", 429], 'resource_id');
        $resourceId = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'t','f','t',2)  RETURNING resource_id ",
            [$clientName, $clientId], 'resource_id');

//        $clientName = Misc::filterNumbers($clientName);

        $rateTableId = $this->getRateTableId();
        $this->addUserData(Misc::filterNumbers($clientName), $rateTableId, $resourceId, $resourceIdP2P, $resourceIdDID, 1, '', 9);


//        $resourceId = $this->insertGetIdToBillingDB("
//                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
//                          values (?,?,'t','f','t',2)  RETURNING resource_id ",
//            ["{$clientName}_PBX", $clientId], 'resource_id');
//
//        $clientName = str_replace('-', '', $clientName);
//        $this->addUserData($clientName . '_PBX', $rateTableId, $resourceId, $resourceIdP2P, $resourceIdDID, 0, $clientName, $clientName . 9);

        // -----------------
        $newResourceId = $this->insertGetIdToBillingDB("
                          insert into resource (alias, ingress, client_id)
                          values (?,'t',?) RETURNING resource_id ",
            ["{$newClientName}_pbx", $clientId], 'resource_id');
        $newResourceIpId = $this->insertGetIdToBillingDB("
                                insert into resource_ip(resource_id, ip)
                                values(?, '108.165.2.110') RETURNING resource_id",
            [$newResourceId], 'resource_id');

        $newClientName = str_replace('-', '', $newClientName);

        $newRateTableId = $this->selectFromBillingDB("select rate_table_id from rate_table where name = 'P2P_ZERO'");
        $newRateTableId = isset($newRateTableId[0]->rate_table_id) ? $newRateTableId[0]->rate_table_id : 0;
        $newRouteStrategyId = $this->selectFromBillingDb("select route_strategy_id from route_strategy where name = '{$newClientName}'");
        $newRouteStrategyId = isset($newRouteStrategyId[0]->route_strategy_id) ? $newRouteStrategyId[0]->route_strategy_id : 0; 
        
        $newResourcePrefixId = $this->insertGetIdToBillingDB("
                          insert into resource_prefix ( resource_id, tech_prefix , rate_table_id, route_strategy_id )
                          values (?,?,?,?) RETURNING id ",
            [$newResourceId, $newClientName, $newRateTableId, $newRouteStrategyId], 'id');

        $newRateTableId = $this->getRateTableId();
        $newRouteStrategyId = $this->selectFromBillingDb("select route_strategy_id from route_strategy where name = '{$newClientName}'");
        $newRouteStrategyId = isset($newRouteStrategyId[0]->route_strategy_id) ? $newRouteStrategyId[0]->route_strategy_id : 0;
        $newResourcePrefixId = $this->insertGetIdToBillingDB("
                          insert into resource_prefix ( resource_id, tech_prefix , rate_table_id, route_strategy_id )
                          values (?,?,?,?) RETURNING id ",
            [$newResourceId, "{$newClientName}9", $newRateTableId, $newRouteStrategyId], 'id');


        $massCallConfig = MassCallConfig::select('id')->where('app_id', '=', $this->app->id)->where('enabled', '=', 1)->first();
        if (isset($massCallConfig->id)) {
            $appRateTableId = $this->selectFromBillingDB("
                                select rate_table_id from rate_table
                                where name = ?", ["{$this->app->tech_prefix}_CC"]);
            $appRateTableId = $this->fetchField($appRateTableId, 'rate_table_id');

            if (!$appRateTableId) {
                $appRateTableId = $this->insertGetIdToBillingDB("
                                    insert into rate_table
                                    (name, currency_id)
                                    values(?, '1') RETURNING rate_table_id",
                    ["{$this->app->tech_prefix}_CC"], 'rate_table_id');
            }

            $appRouteStrategyId = $this->selectFromBillingDB("
                                select route_strategy_id from route_strategy
                                where name = ?", ["{$this->app->tech_prefix}_CC"]);
            $appRouteStrategyId = $this->fetchField($appRouteStrategyId, 'route_strategy_id');
            if (!$appRouteStrategyId) {
                $appRouteStrategyId = $this->insertGetIdToBillingDB("
                                        insert into route_strategy ( name ) 
                                        values (?) RETURNING route_strategy_id",
                    ["{$this->app->tech_prefix}_CC"], 'route_strategy_id');
            }

            $prefix = str_replace('-', '', $this->user->getUserAlias());
            $resourcePrefixId = $this->insertGetIdToBillingDB("
                        insert into resource_prefix ( resource_id, tech_prefix , rate_table_id, route_strategy_id ) 
                        values (?, ?, ?, ?) RETURNING id",
                [$newResourceId, "#{$prefix}", $appRateTableId, $appRouteStrategyId], 'id');
        }
        // -----------------




        $this->getFluentBilling('resource_ip')->insert([
            'resource_id' => $resourceId,
            'ip'          => '108.165.2.110',
            'port'        => 5060
        ]);
    }

    private function addUserData($clientName, $rateTableId, $resourceId, $resourceIdP2P, $resourceIdDID, $regType, $resPrefix1, $resPrefix2)
    {
        $productId = $this->insertGetIdToBillingDB("insert into product (name,code_type)
                                  values (?,0) RETURNING product_id",
            [$clientName], 'product_id');

        $this->createDefaultSipUser($clientName, $resourceId, $productId, $regType);

        $routeStrategyId = $this->insertGetIdToBillingDB("insert into route_strategy (name)
                                  values (?) RETURNING route_strategy_id",
            [$clientName], 'route_strategy_id');

        $this->insertToBillingDB("insert into route (digits, static_route_id, route_type,
                                    route_strategy_id, digits_min_length, digits_max_length)
                                  values (?, ?, 2, ?, 16, 32)",
            [$this->app->tech_prefix, $productId, $routeStrategyId]);

        $appProduct = $this->getFluentBilling('product')->whereName($this->app->tech_prefix)->first();

// new 2016-08-20
//        $this->insertToBillingDB("insert into route (static_route_id, route_type,
//                                    route_strategy_id, digits_min_length, digits_max_length)
//                                  values (?, 2, ?, 0, 15)",
//            [$appProduct->product_id, $routeStrategyId]);
        $dynamicRouteId = $this->selectFromBillingDB("
                                select dynamic_route_id from dynamic_route
                                where name = ?", [$this->app->tech_prefix]);
        $dynamicRouteId = $this->fetchField($dynamicRouteId, 'dynamic_route_id');
        if (!$dynamicRouteId) {
            $dynamicRouteId = $this->insertGetIdToBillingDB("
                                insert into dynamic_route (name)
                                values (?) RETURNING dynamic_route_id",
                [$this->app->tech_prefix], 'dynamic_route_id');
        }

        $this->insertToBillingDB("insert into route (digits, static_route_id, dynamic_route_id, route_type, route_strategy_id,
                                    digits_min_length, digits_max_length)
                                  values ('', 1, ?, 4, ?, 0, 15)",
            [$dynamicRouteId, $routeStrategyId]);
// new 2016-08-20
        $this->insertToBillingDB("insert into resource_prefix (resource_id , tech_prefix ,
                                              route_strategy_id, rate_table_id)
                                  values (?,?,?,2212)",
            [$resourceId, $resPrefix1, $routeStrategyId]);

        $this->insertToBillingDB("insert into resource_prefix (resource_id , tech_prefix ,
                                              route_strategy_id, rate_table_id)
                                  values (?,?,?,?)",
            [$resourceId, $resPrefix2, $routeStrategyId, $rateTableId]);


        $this->insertToBillingDB("
                  INSERT INTO resource_ip(ip, resource_id)
                  VALUES('108.165.2.110', ?)", [$resourceIdDID]);

        $this->insertToBillingDB("
                  INSERT INTO resource_ip(ip, resource_id)
                  VALUES('158.69.203.191', ?)", [$resourceIdP2P]);

        $appDidResource = $this->getResourceByAliasFromBillingDB("{$this->app->getAppAlias()}_DID");

        $this->getFluentBilling('resource_ip')->insert([
            'resource_id' => $appDidResource ? $appDidResource->resource_id: $resourceIdDID,
            'ip'          => '66.226.76.70',
            'port'        => 5060
        ]);
    }

    private function createDefaultSipUser($clientName, $resourceId, $productId, $regType)
    {
        $this->insertToBillingDB("
                          insert into resource_ip (username, password, resource_id, reg_type)
                          values (?,?,?,?)",
            [$clientName, $this->user->raw_password, $resourceId, $regType]);

	    $sipResourceId = $this->insertGetIdToBillingDB("
                                    insert into resource ( alias, egress )
                                    values (?, 't') RETURNING resource_id",
            [$clientName], 'resource_id');

//        $sipResourceId = $this->insertGetIdToBillingDB("
//                                    insert into resource ( alias, egress )
//                                    values (?, 't') RETURNING resource_id",
//            [$clientName], 'resource_id');
//
//        $productItemId = $this->insertGetIdToBillingDB("
//                                    insert into product_items ( product_id, digits )
//                                    values (?, ?) RETURNING item_id",
//            [$productId, $clientName], 'item_id');
//
//        $this->getFluentBilling('product_items_resource')->insert([
//            'item_id'     => $productItemId,
//            'resource_id' => $sipResourceId
//        ]);
//        $this->getFluentBilling('resource_ip')->insert([
//            'resource_id' => $sipResourceId,
//            'ip'          => '158.69.203.191',
//            'port'        => 5060
//        ]);
    }


    private function getRateTableId()
    {
        $rateTableId = $this->selectFromBillingDB("
                                select rate_table_id from rate_table
                                where name = ?", ["{$this->app->tech_prefix}_IDD"]);

        return $this->fetchField($rateTableId, 'rate_table_id');
    }
}
