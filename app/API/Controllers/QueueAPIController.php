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

    /**
     * @SWG\Get(
     *     path="/api/queue/list",
     *     summary="Get queue list",
     *     tags={"queues"},
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
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

    /**
     * @SWG\Post(
     *     path="/api/queue/add",
     *     summary="Create queue",
     *     tags={"queues"},
     *     @SWG\Parameter(
     *         description="Queue name",
     *         name="queue_name",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Client waiting prompt",
     *         name="client_waiting_prompt",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Agent waiting prompt",
     *         name="agent_waiting_prompt",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postAdd()
    {
        $this->setValidator([
            'queue_name'            => 'required|string',
            'client_waiting_prompt' => 'required|string',
            'agent_waiting_prompt'  => 'required|string'
        ]);

        $appId            = $this->getAPPIdByAuthHeader();
        $params           = $this->request->all();
        $params['app_id'] = $appId;

        $response = $this->makeErrorResponse('Failed to create queue');

        if (Queue::create($params))
            $response = $this->defaultResponse(['result' => 'Queue has been created']);

        return $response;
    }

    /**
     * @SWG\Post(
     *     path="/api/queue/delete",
     *     summary="Delete queue",
     *     tags={"queues"},
     *     @SWG\Parameter(
     *         description="Queue name",
     *         name="queue_name",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
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
