<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;

class DeleteAPPUserFromBillingDB extends Job implements SelfHandling
{
    use BillingTrait;

    protected $user;
    protected $app;

    /**
     * Create a new job instance.
     *
     * @param $user
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->app  = $user->app;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $clientName = $this->user->getUserAlias();
        $this->getFluentBilling('client')->whereName($clientName)->delete();
        $this->getFluentBilling('resource')->whereAlias($clientName)->delete();
        $this->getFluentBilling('resource')->whereAlias("{$clientName}_DID")->delete();
    }
}
