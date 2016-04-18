<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\API\DIDXMLActionBuilder;
use App\Helpers\APILogger;
use App\Models\AppUser;
use App\Models\ConferenceLog;
use App\Models\DID;
use App\Models\QueueAgentSession;
use App\Models\QueueCallerSession;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Webpatser\Uuid\Uuid;

class FreeswitchController extends Controller
{
    use Helpers, APIHelperTrait;


    /**
     * FreeswitchController constructor.
     */
    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/fs/get_call_handler",
     *     summary="Get call handler",
     *     tags={"freeswitch"},
     *     @SWG\Parameter(
     *         description="DID",
     *         name="dnis",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Call handler response"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="404", description="Not found"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getCallHandler(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'dnis' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $did = $this->findDID($request->dnis);
        if (!$did)
            return $this->response->errorNotFound('DID not found');
        $xml = $this->getDIDXmlResponse($did);

        return $this->makeResponse($did, $xml);
    }

    /**
     * @SWG\Get(
     *     path="/api/fs/join_conference",
     *     summary="Join conference",
     *     tags={"freeswitch"},
     *     @SWG\Parameter(
     *         description="DID",
     *         name="dnis",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="ANI",
     *         name="ani",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="UUID",
     *         name="uuid",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Conference name",
     *         name="conf_name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Conference Id",
     *         name="conf_id",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getJoinConference(Request $request)
    {
        $this->setValidator([
            'dnis'      => 'required',
            'ani'       => 'required',
            'uuid'      => 'required|uuid',
            'conf_name' => 'required|string',
            'conf_id'   => 'required|exists:conference,id'
        ]);

        $did = $this->findDID($request->dnis);
        if (!$did)
            return ['error' => 'DID not found'];

        return $this->createConferenceLogRecord($request, $did);
    }

    /**
     * @SWG\Get(
     *     path="/api/fs/leave_conference",
     *     summary="Leave conference",
     *     tags={"freeswitch"},
     *     *@SWG\Parameter(
     *         description="DID",
     *         name="dnis",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="ANI",
     *         name="ani",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="UUID",
     *         name="uuid",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Conference name",
     *         name="conf_name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Conference Id",
     *         name="conf_id",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getLeaveConference(Request $request)
    {
        $this->setValidator([
            'uuid'      => 'required|uuid',
            'dnis'      => 'required',
            'ani'       => 'required',
            'conf_name' => 'required|string',
            'conf_id'   => 'required|exists:conference,id'
        ]);

        $enterSession = ConferenceLog::whereUuid($request->uuid)->first();
        if (!$enterSession)
            return ['error' => 'Couldn\'t find enter conference session record'];
        $enterSession['owned_by'] = $enterSession->user_id;

        return $this->createConferenceLogRecord($request, $enterSession, false);
    }

    /**
     * @SWG\Get(
     *     path="/api/fs/agent_join_queue",
     *     summary="Join agent queue",
     *     tags={"freeswitch"},
     *     @SWG\Parameter(
     *         description="DID",
     *         name="dnis",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="ANI",
     *         name="ani",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="UUID",
     *         name="uuid",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Queue name",
     *         name="queue_name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Queue Id",
     *         name="queue_id",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getAgentQueueJoin(Request $request)
    {
        $this->setValidator([
            'dnis'       => 'required',
            'ani'        => 'required',
            'uuid'       => 'required|uuid',
            'queue_name' => 'required|string',
            'queue_id'   => 'required|exists:queue,id',
        ]);

        $did = $this->findDID($request->dnis);
        if (!$did)
            return ['error' => 'DID not found'];

        return $this->createQueueSessionRecord($request, $did, new QueueAgentSession());
    }

    /**
     * @SWG\Get(
     *     path="/api/fs/agent_leave_queue",
     *     summary="Leave agent queue",
     *     tags={"freeswitch"},
     *     @SWG\Parameter(
     *         description="DID",
     *         name="dnis",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="ANI",
     *         name="ani",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="UUID",
     *         name="uuid",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Queue name",
     *         name="queue_name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Queue Id",
     *         name="queue_id",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getAgentQueueLeave(Request $request)
    {
        $this->setValidator([
            'dnis'       => 'required',
            'ani'        => 'required',
            'uuid'       => 'required|uuid',
            'queue_name' => 'required|alpha',
            'queue_id'   => 'required|exists:queue,id',
        ]);

        $enterSession = QueueAgentSession::whereUuid($request->uuid)->first();

        if (!$enterSession)
            return ['error' => 'Couldn\'t find enter queue session record'];

        return $this->createQueueSessionRecord($request, $enterSession, new QueueAgentSession(), false);
    }

    protected function createConferenceLogRecord($request, $did, $enterConference = true)
    {
        $conferenceLog                = new ConferenceLog();
        $conferenceLog->app_id        = $did->app_id;
        $conferenceLog->conference_id = $request->conf_id;
        $conferenceLog->name          = $request->conf_name;
        $conferenceLog->enter_time    = $enterConference ? date('Y-m-d H:i:s') : null;
        $conferenceLog->leave_time    = !$enterConference ? date('Y-m-d H:i:s') : null;
        $conferenceLog->caller_id     = $did->caller_id ?: $request->ani;
        $conferenceLog->uuid          = $request->uuid;
        $conferenceLog->user_id       = $did->owned_by ?: 0;
        $conferenceLog->is_owner      = 0;

        return $conferenceLog->save() ? ['result' => 'ok'] : ['error' => ''];

    }

    protected function createQueueSessionRecord($request, $did, $queueSession, $enterSession = true)
    {
        $queueSession->app_id     = $did->app_id;
        $queueSession->caller_id  = $request->ani;
        $queueSession->queue_id   = $request->queue_id;
        $queueSession->uuid       = $request->uuid;
        $queueSession->queue_name = $request->queue_name;
        $queueSession->join_time  = $enterSession ? date('Y-m-d H:i:s') : null;
        $queueSession->leave_time = !$enterSession ? date('Y-m-d H:i:s') : null;

        return $queueSession->save() ? ['result' => 'ok'] : ['error' => ''];
    }

    public function getCallerQueueJoin(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'dnis'       => 'required',
            'ani'        => 'required',
            'uuid'       => 'required',
            'queue_name' => 'required',
            'queue_id'   => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $did = $this->findDID($request->dnis);
        if (!$did)
            return ['error' => 'DID not found'];

        return $this->createQueueSessionRecord($request, $did, new QueueCallerSession());
    }

    public function getCallerQueueLeave(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'uuid'       => 'required',
            'queue_name' => 'required',
            'queue_id'   => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }
        $enterSession = QueueCallerSession::whereUuid($request->uuid)->first();

        if (!$enterSession)
            return ['error' => 'Couldn\'t find enter queue session record'];

        return $this->createQueueSessionRecord($request, $enterSession, new QueueCallerSession(), false);
    }

    protected function findDID($dnis)
    {
        $selectFields = [
            'did.id',
            'app_id',
            'owned_by',
            'action_id',
            'did_action.name'
        ];

        return DID::select($selectFields)->whereDid($dnis)->whereNull('deleted_at')->action()->first();
    }

    protected function getDIDXmlResponse($did)
    {

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><action></action>');
        $xml->addAttribute('type', $did->name);
        $params = $xml->addChild('parameters');

        $actionParameters = $did->actionParameters()->joinParamTable()->get();
        foreach ($actionParameters as $param) {
            $node = $params->addChild('parameter', $param->parameter_value);
            $node->addAttribute('name', $param->name);
        }


        return $xml->asXML();
    }

    /**
     * @SWG\Post(
     *     path="/dialplan",
     *     summary="Return freeswitch XML",
     *     tags={"freeswitch"},
     *     @SWG\Parameter(
     *         description="Caller ANI",
     *         name="Caller-ANI",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Caller Destination Number",
     *         name="Caller-Destination-Number",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="XML"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getFreeswitchResponse(Request $request)
    {
        $this->setValidator([
            'Caller-ANI'                => 'required',
            'Caller-Destination-Number' => 'required|numeric'
        ]);

        APILogger::log($request->all(), 'Freeswitch API request');

        $did = $this->findDID($request->input('Caller-Destination-Number'));

        return $this->getFreeswitchXmlResponse($did);
    }

    protected function getFreeswitchXmlResponse($did)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><document></document>');
        $xml->addAttribute('type', 'freeswitch/xml');
        $section = $xml->addChild('section');
        $section->addAttribute('name', 'dialplan');
        $section->addAttribute('description', 'dialplan');
        $context = $section->addChild('context');
        $context->addAttribute('name', 'default');
        $extension = $context->addChild('extension');
        if ($did and $did->name == 'IVR')
            $extension->addAttribute('name', 'play_and_get_digits example');
        else $extension->addAttribute('name', 'test9');
        $condition = $extension->addChild('condition');
        $condition->addAttribute('field', 'destination_number');
        $condition->addAttribute('expression', '^(.*)$');
        if ($did) {
            if ($did->name == 'HTTP Action Request')
                return $this->makeXMLForHTTPRequestAction($did, $condition, $xml);
            $this->makeXMLActionNode($did, $condition);
        } else {
            $action = $condition->addChild('action');
            $action->addAttribute('application', 'playback');
            $action->addAttribute('data', 'intro_prompt');
        }

        APILogger::log($xml->asXML(), 'XML API Response');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    protected function makeXMLActionNode($did, $condition)
    {
        $didActionBuilder = new DIDXMLActionBuilder($did, $condition);
        $didActionBuilder->build();

    }

    protected function makeXMLForHTTPRequestAction($did, $condition, $xml)
    {
        $request          = Request::capture();
        $actionParameter  = $did->actionParameters()->joinParamTable()->first();
        $actionParameter  = $actionParameter ? $actionParameter->parameter_value : '';
        $actionParameter .= '?callerid='.$request->input('Caller-ANI');
        $actionParameter .= '&call-to='.$request->input('Caller-Destination-Number');
        $client           = new Client();
        $request          = $client->request('GET', $actionParameter);
        $stringXML        = (string) $request->getBody();
        $this->parseResponseXML($stringXML, $condition);

        APILogger::log($xml->asXML(), 'XML API Response');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    protected function parseResponseXML($stringXML, $condition)
    {
        $simpleXml = new SimpleXMLElement($stringXML);
        $action    = $condition->addChild('action');
        $this->appendXMLAttributes($simpleXml->attributes(), $action);
        $this->appendXMLChildren($simpleXml, $action);
    }

    protected function appendXMLChildren($element, $parent)
    {
        $children = $element->children();

        foreach ($children as $child) {
            $appendedChild = $parent->addChild($child->getName(), (string) $child);
            if ($child->attributes())
                appendAttributes($child->attributes(), $appendedChild);
            if ($child->children())
                appendChildren($child, $appendedChild);
        }
    }

    protected function appendXMLAttributes($attributes, $parent)
    {
        $attr = $parent->attributes();
        foreach ($attributes as $attribute) {
            if (!isset($attr[$attribute->getName()]))
                $parent->addAttribute($attribute->getName(), (string) $attribute);
        }
    }

    /**
     * @SWG\Post(
     *     path="/user",
     *     summary="Return freeswitch user XML",
     *     tags={"freeswitch"},
     *     @SWG\Parameter(
     *         description="APP user's email",
     *         name="user",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="XML"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="404", description="Not found"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return bool|mixed
     */
    public function getFreeswitchUser(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'user' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $user = AppUser::whereEmail($request->user)->first();

        if (!$user)
            return new Response('User not found', 404, []);
        else return $this->getFreeswitchUserXmlResponse($user);

    }

    protected function getFreeswitchUserXmlResponse($user)
    {
        $opensips_ip = env('OPENSIPS_IP', '158.69.203.191');
        $xml         = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><document></document>');
        $xml->addAttribute('type', 'freeswitch/xml');
        $section = $xml->addChild('section');
        $section->addAttribute('name', 'directory');
        $domain = $section->addChild('domain');
        $domain->addAttribute('name', 'default');
        $param = $domain->addChild('params')->addChild('param');
        $param->addAttribute('dial-string', "$user->id@$opensips_ip");
        $group = $domain->addChild('groups')->addChild('group');
        $group->addAttribute('name', '18');
        $userNode = $group->addChild('users')->addChild('user');
        $userNode->addAttribute('id', "$user->app_id");
        $params = $userNode->addChild('params');
        $param  = $params->addChild('param');
        $param->addAttribute('name', 'password');
        $param->addAttribute('value', 'xxx');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    protected function makeResponse($did, $xml)
    {
        $response = [
            'app_id'      => $did->app_id,
            'tech_prefix' => $did->appUser ? $did->appUser->tech_prefix : '',
            'handler_xml' => $xml
        ];

        APILogger::log($response, 'API Response');

        return $this->response->array($response);
    }

}
