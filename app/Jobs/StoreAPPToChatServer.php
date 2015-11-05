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

    /**
     * Create a new job instance.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $name     = $this->app->name;
        $owner    = Auth::user()->email;
        $this->createHttpClient();
        $response = $this->sendRequest("company/$name/$owner");
    }
}
