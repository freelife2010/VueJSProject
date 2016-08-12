<?php

namespace App\Jobs;

use App\Helpers\BillingTrait;
use App\Helpers\PlaySMSTrait;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteDeveloperFromBillingDB extends Job implements SelfHandling
{
    use InteractsWithQueue, SerializesModels, BillingTrait, PlaySMSTrait;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @param $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = $this->getFluentBilling('client')->whereName($this->email)->delete();
    }
}
