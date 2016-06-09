<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Jobs\Job;
use Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;

class StoreAPPToBillingDB extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels, BillingTrait;

    protected $app;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param $app
     * @param null $user
     */
    public function __construct($app, $user = null)
    {
        $this->app  = $app;
        $this->user = $user ?: Auth::user();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->checkAliasExistence();
        $currencyId  = $this->getCurrencyIdFromBillingDB();
        $rateTableId = $this->insertGetIdToBillingDB('
                            insert into rate_table
                            (name,currency_id)
                            values (?,?)  RETURNING rate_table_id',
            ["{$this->app->tech_prefix}_IDD", $currencyId], 'rate_table_id');
        $clientId   = $this->getCurrentUserIdFromBillingDB($this->user);

        $this->createRates($rateTableId);

        $rateTableDIDId = $this->insertGetIdToBillingDB('
                            insert into rate_table
                            (name,currency_id)
                            values (?,?)  RETURNING rate_table_id',
            ["{$this->app->tech_prefix}_DID", $currencyId], 'rate_table_id');

        $this->createRates($rateTableDIDId);

        $resourceId      = $this->createResource($rateTableId, $clientId);
        $routeStrategyId = $this->createRouteStrategy();
        $this->createProducts($resourceId, $routeStrategyId);

        $this->insertToBillingDB("
                  INSERT INTO resource_ip(ip, resource_id)
                  VALUES('69.27.168.50', ?)", [$resourceId]);

        $this->createRouting($rateTableId, $clientId);

    }

    private function createRates($rateTableId)
    {
        $countRates = 9;
        for ($i = 1; $i <= $countRates; $i++) {
            $this->insertToBillingDB('insert into rate (rate_table_id,code,rate,effective_date)
                                      values (?,?,?,current_timestamp(0));',
                                        [$rateTableId, $i, 100]);
        }
    }

    private function createResource($rateTableId, $clientId)
    {
        $resourceId = $this->insertGetIdToBillingDB("
                              insert into resource
                              (alias,client_id,rate_table_id,ingress,egress,enough_balance,media_type)
                              values (?,?,?,'f','t','t',2)
                              RETURNING resource_id",
                                [$this->app->getAppAlias(), $clientId, $rateTableId],
                                'resource_id');

        return $resourceId;
    }

    private function createRouteStrategy()
    {
        $routeStrategyId = $this->insertGetIdToBillingDB("
                                    insert into route_strategy (name)
                                    values (?) RETURNING route_strategy_id",
                                    [$this->app->getAppAlias()], 'route_strategy_id');

        return $routeStrategyId;
    }

    private function createProducts($resourceId, $routeStrategyId)
    {
        $productId = $this->insertGetIdToBillingDB(
                             "insert into product (name)
                              values (?) RETURNING product_id",
                              [$this->app->tech_prefix], 'product_id');
        $itemId    = $this->insertGetIdToBillingDB(
                             "insert into product_items (product_id)
                              values (?) RETURNING item_id",
                              [$productId], 'item_id');
        $this->insertToBillingDB("
                  insert into product_items_resource(item_id, resource_id)
                  VALUES (?, ?)", [$itemId, $resourceId]);
        $this->insertToBillingDB("
                  INSERT INTO route(digits, static_route_id, route_type, route_strategy_id,
                                    digits_min_length, digits_max_length)
                  VALUES('', ?, 2, ?, 0, 16)", [$productId, $routeStrategyId]);
    }

    public function createStaticRoute()
    {

        $staticRouteId = $this->insertGetIdToBillingDB(
            "insert into product (name,modify_time,
                                  update_by,code_type,code_deck_id,route_lrn)
                          values (?, CURRENT_TIMESTAMP (0), 'admin', 0, 1, 1) RETURNING product_id",
            ["static_route"], 'product_id');

        $this->getFluentBilling('route_strategy')->insert([
            'name'      => 'plan',
            'update_at' => \DB::raw('CURRENT_TIMESTAMP (0)'),
            'update_by' => 'admin'
        ]);

        return $staticRouteId;
    }

    public function createRoute($routeStrategyId, $staticRouteId)
    {
        $this->getFluentBilling('route')->insert([
            'route_type_flg"' => 2,
            'route_strategy_id' => $routeStrategyId,
            'ani_prefix' => 2,
            'digits' => 3,
            'route_type' => 2,
            'static_route_id' => $staticRouteId,
            'update_at' => \DB::raw('CURRENT_TIMESTAMP (0)'),
            'update_by' => 'admin'
        ]);
    }

    public function failed()
    {
        $this->delete();
    }

    public function createRouting($rateTableId, $clientId)
    {
        $didResourceId = $this->insertGetIdToBillingDB("
                              insert into resource
                              (alias,ingress,active, client_id)
                              values (?,'t','t', ?)
                              RETURNING resource_id",
            ["{$this->app->getAppAlias()}_DID", $clientId],
            'resource_id');
        $iddResourceId = $this->insertGetIdToBillingDB("
                              insert into resource
                              (alias,egress,active, rate_table_id, client_id)
                              values (?,'t','t', ?, ?)
                              RETURNING resource_id",
            ["{$this->app->getAppAlias()}_IDD", $rateTableId, $clientId],
            'resource_id');
    }

    /**
     * @throws \Exception
     */
    private function checkAliasExistence()
    {
        $exists = $this->getResourceByAliasFromBillingDB($this->app->getAppAlias());

        if ($exists) throw new \Exception('Unique violation. App alias already exists');
    }

}
