<?php

namespace App\Listeners;

use App\API\APIHelperTrait;
use Dingo\Api\Event\ResponseWasMorphed;
use Dingo\Api\Http\Request;

class OptionalAPIParamsChecker
{
    use APIHelperTrait;

    protected $request;
    protected $response;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->request = Request::capture();
    }

    /**
     * Handle the event.
     *
     * @param ResponseisMorphing|ResponseWasMorphed $event
     * @param Request $request
     */
    public function handle(ResponseWasMorphed $event)
    {
        $optionalParams = $this->getOptionalParams();
        $this->response = $event->response;
        foreach ($optionalParams as $param) {
            if ($this->request->getMethod() == 'GET'
            and $this->request->has($param['name']))
                $this->$param['method']($event);
        }

    }

}
