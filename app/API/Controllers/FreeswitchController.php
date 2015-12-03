<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
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

class FreeswitchController extends Controller
{
    use Helpers, APIHelperTrait;

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
            return ['error' => 'DID not found'];
        $xml = $this->getDIDXmlResponse($did);

        return $this->makeResponse($did, $xml);
    }

    public function getJoinConference(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'dnis'      => 'required',
            'ani'       => 'required',
            'uuid'      => 'required',
            'conf_name' => 'required',
            'conf_id'   => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $did = $this->findDID($request->dnis);
        if (!$did)
            return ['error' => 'DID not found'];

        return $this->createConferenceLogRecord($request, $did);
    }

    public function getLeaveConference(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'uuid'      => 'required',
            'conf_name' => 'required',
            'conf_id'   => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $enterSession = ConferenceLog::whereUuid($request->uuid)->first();
        if (!$enterSession)
            return ['error' => 'Couldn\'t find enter conference session record'];
        $enterSession['owned_by'] = $enterSession->user_id;

        return $this->createConferenceLogRecord($request, $enterSession, false);
    }

    public function getAgentQueueJoin(Request $request)
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

        return $this->createQueueSessionRecord($request, $did, new QueueAgentSession());
    }

    public function getAgentQueueLeave(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'uuid'       => 'required',
            'queue_name' => 'required',
            'queue_id'   => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }
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

        return DID::select($selectFields)->whereDid($dnis)->action()->first();
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

    public function getFreeswitchResponse(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'Caller-ANI'                => 'required',
            'Caller-Destination-Number' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

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
        $extension->addAttribute('name', 'test9');
        $condition = $extension->addChild('condition');
        $condition->addAttribute('field', 'destination_number');
        $condition->addAttribute('expression', '^(.*)$');
        if ($did->name == 'HTTP Action Request')
            return $this->makeXMLForHTTPRequestAction($did, $condition, $xml);
        $action = $condition->addChild('action');
        if ($did) {
            $this->makeXMLActionNode($did, $action);
        } else {
            $action->addAttribute('application', 'playback');
            $action->addAttribute('data', 'intro_prompt');
        }

        return new Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    protected function makeXMLActionNode($did, $action) {
        $actionParameter = $did->actionParameters()->joinParamTable()->first();
        $actionParameter = $actionParameter ? $actionParameter->parameter_value : '';
        if ($did->name == 'Forward to user') {
            $did->name = 'bridge';
            $actionParameter = "sofia/internal/$actionParameter@69.27.168.16:5060";
        }
        if ($did->name == 'Forward to number') {
            $did->name = 'bridge';
            $actionParameter = "sofia/internal/$actionParameter@69.27.168.11";
        }
        $action->addAttribute('application', $did->name);
        $action->addAttribute('data', $actionParameter);
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

        return new Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    protected function parseResponseXML($stringXML, $condition)
    {
        $actions   = explode('<Action type=\'', $stringXML);
        array_shift($actions);
        foreach ($actions as $action) {
            $endPos     = strpos($action, '\'>');
            $endPos     = $endPos !== false ? $endPos : strpos($action, '\' />');
            $actionName = substr($action, 0, $endPos);
            $startPos   = $endPos + strlen('\'>');
            $endPos     = strpos($action, '</');
            $paramValue = substr($action, $startPos, $endPos-$startPos);
            $paramValue = preg_replace('/[^a-zA-Z0-9]/s', '', $paramValue);
            $action     = $condition->addChild('action');
            $action->addAttribute('application', $actionName);
            if ($paramValue)
                $action->addAttribute('data', $paramValue);
        }
    }

    protected function makeResponse($did, $xml)
    {
        $response = [
            'app_id'      => $did->app_id,
            'tech_prefix' => $did->appUser ? $did->appUser->tech_prefix : '',
            'handler_xml' => $xml
        ];

        return $this->response->array($response);
    }

}
