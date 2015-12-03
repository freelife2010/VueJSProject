<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;

class StoreAPPUserToBillingDB extends Job implements SelfHandling
{
    use BillingTrait;

    protected $user;
    protected $app;

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
        $currencyId = $this->getCurrencyIdFromBillingDB();
        $clientName = $this->app->name."-".$this->user->email;
        $clientId   = $this->insertGetIdToBillingDB("
                              insert into client
                              (name,currency_id,unlimited_credit,mode,enough_balance)
                              values (?,?,'t',2,'t') RETURNING client_id",
                              [$clientName, $currencyId], 'client_id');
        $this->insertToBillingDB("
                  insert into client_balance (client_id,balance,ingress_balance)
                  values (?,0,0) ", [$clientId]);

        $clientAlias = $this->user->getUserAlias($clientId, $this->app);
        $resourceId = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'t','f','t',2)  RETURNING resource_id ",
                          [$clientAlias, $clientId], 'resource_id');
        $this->insertToBillingDB("
                          insert into resource_ip (username,direction,resource_id)
                          values (?,0,?)",
            [ $this->user->user_id, $resourceId]);
        $routeStrategyId = $this->getRouteStrategyId();
        $rateTableId     = $this->getRateTableId();

        $this->insertToBillingDB("insert into resource_prefix (resource_id,route_strategy_id,rate_table_id)
                                  values (?,?,?)",
                                  [$resourceId, $routeStrategyId, $rateTableId]);

    }

    private function getRouteStrategyId()
    {
        $routeStrategyId = $this->selectFromBillingDB("
                                select route_strategy_id from route_strategy
                                where name = ?", [$this->app->name]);

        return $this->fetchField($routeStrategyId, 'route_strategy_id');
    }

    private function getRateTableId()
    {
        $rateTableId = $this->selectFromBillingDB("
                                select rate_table_id from rate_table
                                where name = ?", [$this->app->name]);

        return $this->fetchField($rateTableId, 'rate_table_id');
    }
}
