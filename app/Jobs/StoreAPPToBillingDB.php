<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Jobs\Job;
use Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

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
            [$this->app->tech_prefix, $currencyId], 'rate_table_id');

        $this->createRates($rateTableId);

        $resourceId      = $this->createResource($rateTableId);
        $routeStrategyId = $this->createRouteStrategy();
        $this->createProducts($resourceId, $routeStrategyId);
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

    private function createResource($rateTableId)
    {
        $clientId   = $this->getCurrentUserIdFromBillingDB($this->user);
        $resourceId = $this->insertGetIdToBillingDB("
                              insert into resource
                              (alias,client_id,rate_table_id,ingress,egress,enough_balance,media_type)
                              values (?,?,?,'f','t','t',2)
                              RETURNING resource_id",
                                [$this->app->alias, $clientId, $rateTableId],
                                'resource_id');

        return $resourceId;
    }

    private function createRouteStrategy()
    {
        $routeStrategyId = $this->insertGetIdToBillingDB("
                                    insert into route_strategy (name)
                                    values (?) RETURNING route_strategy_id",
                                    [$this->app->name], 'route_strategy_id');

        return $routeStrategyId;
    }

    private function createProducts($resourceId, $routeStrategyId)
    {
        $productId = $this->insertGetIdToBillingDB(
                             "insert into product (name)
                              values (?) RETURNING product_id",
                              [$this->app->name], 'product_id');
        $itemId    = $this->insertGetIdToBillingDB(
                             "insert into product_items (product_id)
                              values (?) RETURNING item_id",
                              [$productId], 'item_id');
        $this->insertToBillingDB("
                  insert into product_items_resource(item_id, resource_id)
                  VALUES (?, ?)", [$itemId, $resourceId]);
        $this->insertToBillingDB("
                  INSERT INTO route(digits, static_route_id, route_type, route_strategy_id)
                  VALUES('', ?, 2, ?)", [$productId, $routeStrategyId]);
    }

    public function failed()
    {
        $this->delete();
    }

    /**
     * @throws \Exception
     */
    private function checkAliasExistence()
    {
        $exists = $this->getResourceByAliasFromBillingDB($this->app->alias);

        if ($exists) throw new \Exception('Unique violation. App alias already exists');
    }

}
