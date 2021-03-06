<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Helpers\PlaySMSTrait;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreDeveloperToBillingDB extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels, BillingTrait, PlaySMSTrait;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @param $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $currencyId = $this->getCurrencyIdFromBillingDB();
        $clientId   = $this->insertGetIdToBillingDB("
                            insert into client
                            (name,currency_id,unlimited_credit,mode,enough_balance)
                            values (?,?,?,?,?) RETURNING client_id",
            [$this->user->email, $currencyId, true, 2, true], 'client_id');
        $this->insertToBillingDB("
                    insert into c4_client_balance (client_id,balance,ingress_balance)
                    values (?,?,?)",
            [$clientId, 0, 0]);

        $this->createSMSAccount($this->user);
    }
}
