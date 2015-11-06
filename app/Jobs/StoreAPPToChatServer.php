<?php

namespace App\Jobs;

use App\API\ApiClient\GuzzleClient;
use App\Jobs\Job;
use Auth;
use Illuminate\Contracts\Bus\SelfHandling;

class StoreAPPToChatServer extends Job implements SelfHandling
{
    use GuzzleClient;
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
        $name     = $this->app->name;
        $owner    = $this->user->email;
        $this->createHttpClient();
        $response = $this->sendRequest("company/$name/$owner");
    }
}
