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
                              values (?,?,'t',2,'t') RETURNING client_id",
            [$clientName, $currencyId], 'client_id');
        $this->insertToBillingDB("
                  insert into c4_client_balance (client_id,balance,ingress_balance)
                  values (?,0,0) ", [$clientId]);

        $this->insertToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'f','t','t',2)",
            ["{$clientName}_DID", $clientId]);
        $resourceId = $this->insertGetIdToBillingDB("
                          insert into resource (alias,client_id,ingress,egress,enough_balance,media_type)
                          values (?,?,'t','f','t',2)  RETURNING resource_id ",
            [$clientName, $clientId], 'resource_id');
        $clientName = Misc::filterNumbers($clientName);

        $rateTableId = $this->getRateTableId();

        $this->addUserData($clientName, $rateTableId, $resourceId);

    }

    private function addUserData($clientName, $rateTableId, $resourceId)
    {
        $productId = $this->insertGetIdToBillingDB("insert into product (name,code_type)
                                  values (?,0) RETURNING product_id",
            [$clientName], 'product_id');

        $this->createDefaultSipUser($clientName, $resourceId, $productId);

        $routeStrategyId = $this->insertGetIdToBillingDB("insert into route_strategy (name)
                                  values (?) RETURNING route_strategy_id",
            [$clientName], 'route_strategy_id');

        $this->insertToBillingDB("insert into route_record (static_route_id, route_type, route_strategy_id)
                                  values (?, 2, ?)",
            [$productId, $routeStrategyId]);

        $this->insertToBillingDB("insert into resource_prefix (resource_id , tech_prefix ,
                                              route_strategy_id, rate_table_id)
                                  values (?,'',?,2212)",
            [$resourceId, $routeStrategyId]);

        $this->insertToBillingDB("insert into resource_prefix (resource_id , tech_prefix ,
                                              route_strategy_id, rate_table_id)
                                  values (?,'9',59,?)",
            [$resourceId, $rateTableId]);
    }

    private function createDefaultSipUser($clientName, $resourceId, $productId)
    {
        $this->insertToBillingDB("
                          insert into resource_ip (username, password, direction,resource_id)
                          values (?,?,0,?)",
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
