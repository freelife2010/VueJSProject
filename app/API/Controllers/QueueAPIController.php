<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Models\Queue;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class QueueAPIController extends Controller
{
    use Helpers, APIHelperTrait;

    public function __construct()
    {
        $this->initAPI();
    }

    public function getList()
    {
        $appId  = $this->getAPPIdByAuthHeader();
        $fields = [
            'id',
            'queue_name',
            'client_waiting_prompt',
            'agent_waiting_prompt',
            'created_at'
        ];

        $queues = Queue::select($fields)->whereAppId($appId)->get();

        return $this->defaultResponse(['entities' => $queues]);
    }

    public function postAdd()
    {
        $this->setValidator([
            'queue_name'            => 'required',
            'client_waiting_prompt' => 'required',
            'agent_waiting_prompt'  => 'required'
        ]);

        $appId            = $this->getAPPIdByAuthHeader();
        $params           = $this->request->all();
        $params['app_id'] = $appId;

        $response = $this->makeErrorResponse('Failed to create queue');

        if (Queue::create($params))
            $response = $this->defaultResponse(['result' => 'Queue has been created']);

        return $response;
    }

    public function postDelete()
    {
        $this->setValidator([
            'queue_name' => 'required|exists:queue,queue_name',
        ]);

        $appId    = $this->getAPPIdByAuthHeader();
        $response = $this->makeErrorResponse('Failed to delete queue');
        $queues   = Queue::whereAppId($appId)->whereQueueName($this->request->queue_name)->get();

        if ($queues) {
            foreach ($queues as $queue) {
                $queue->delete();
            }

            $response = $this->defaultResponse(['result' => 'Queue has been deleted']);
        }

        return $response;
    }
}
