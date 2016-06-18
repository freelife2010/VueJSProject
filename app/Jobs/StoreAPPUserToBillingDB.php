<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Helpers\Misc;
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
        $clientName = $this->user->getUserAlias();
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
        $resourceIdP2P = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'f','t','t',2) RETURNING resource_id",
            ["{$clientName}_P2P", $clientId], 'resource_id');
        $resourceId = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'t','f','t',2)  RETURNING resource_id ",
            [$clientName, $clientId], 'resource_id');
        $clientName = Misc::filterNumbers($clientName);

        $rateTableId = $this->getRateTableId();

        $this->addUserData($clientName, $rateTableId, $resourceId, $resourceIdP2P, $resourceIdDID);

    }

    private function addUserData($clientName, $rateTableId, $resourceId, $resourceIdP2P, $resourceIdDID)
    {
        $productId = $this->insertGetIdToBillingDB("insert into product (name,code_type)
                                  values (?,0) RETURNING product_id",
            [$clientName], 'product_id');

        $this->createDefaultSipUser($clientName, $resourceId, $productId);

        $routeStrategyId = $this->insertGetIdToBillingDB("insert into route_strategy (name)
                                  values (?) RETURNING route_strategy_id",
            [$clientName], 'route_strategy_id');

        $this->insertToBillingDB("insert into route (digits, static_route_id, route_type,
                                    route_strategy_id, digits_min_length, digits_max_length)
                                  values (?, ?, 2, ?, 16, 32)",
            [$this->app->tech_prefix, $productId, $routeStrategyId]);

        $appProduct = $this->getFluentBilling('product')->whereName($this->app->tech_prefix)->first();


        $this->insertToBillingDB("insert into route (static_route_id, route_type,
                                    route_strategy_id, digits_min_length, digits_max_length)
                                  values (?, 2, ?, 0, 15, ?)",
            [$appProduct->product_id, $routeStrategyId]);

        $this->insertToBillingDB("insert into resource_prefix (resource_id , tech_prefix ,
                                              route_strategy_id, rate_table_id)
                                  values (?,'',?,2212)",
            [$resourceId, $routeStrategyId]);

        $this->insertToBillingDB("insert into resource_prefix (resource_id , tech_prefix ,
                                              route_strategy_id, rate_table_id)
                                  values (?,'9',?,?)",
            [$resourceId, $routeStrategyId, $rateTableId]);


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

    private function createDefaultSipUser($clientName, $resourceId, $productId)
    {
        $this->insertToBillingDB("
                          insert into resource_ip (username, password, resource_id, reg_type)
                          values (?,?,?, 1)",
            [$clientName, $this->user->raw_password, $resourceId]);

        $sipResourceId = $this->insertGetIdToBillingDB("
                                    insert into resource ( alias, egress )
                                    values (?, 't') RETURNING resource_id",
            [$clientName], 'resource_id');

        $productItemId = $this->insertGetIdToBillingDB("
                                    insert into product_items ( product_id, digits )
                                    values (?, ?) RETURNING item_id",
            [$productId, $clientName], 'item_id');

        $this->getFluentBilling('product_items_resource')->insert([
            'item_id'     => $productItemId,
            'resource_id' => $sipResourceId
        ]);
        $this->getFluentBilling('resource_ip')->insert([
            'resource_id' => $sipResourceId,
            'ip'          => '158.69.203.191',
            'port'        => 5060
        ]);
    }


    private function getRateTableId()
    {
        $rateTableId = $this->selectFromBillingDB("
                                select rate_table_id from rate_table
                                where name = ?", ["{$this->app->tech_prefix}_IDD"]);

        return $this->fetchField($rateTableId, 'rate_table_id');
    }
}
