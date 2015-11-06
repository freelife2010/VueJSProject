<?php

namespace App\Jobs;

use App\API\ApiClient\GuzzleClient;
use App\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;

class DeleteAPPUserFromChatServer extends Job implements SelfHandling
{
    use GuzzleClient;
    protected $user;
    /**
     * Create a new job instance.
     *
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
        $email    = $this->user->email;
        $appName  = $this->user->app->name;
        $this->createHttpClient();
        $response = $this->sendRequest("remove-member/$email/$appName");
    }
}
