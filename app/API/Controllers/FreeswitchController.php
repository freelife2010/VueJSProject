<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Models\ConferenceLog;
use App\Models\DID;
use App\Models\QueueAgentSession;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleXMLElement;

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

        return $this->createQueueAgentSessionRecord($request, $did);
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
            return ['error' => 'Couldn\'t find enter conference session record'];

        return $this->createQueueAgentSessionRecord($request, $enterSession, false);
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

    protected function createQueueAgentSessionRecord($request, $did, $enterSession = true)
    {
        $agentSession             = new QueueAgentSession();
        $agentSession->app_id     = $did->app_id;
        $agentSession->queue_id   = $request->queue_id;
        $agentSession->uuid       = $request->uuid;
        $agentSession->queue_name = $request->queue_name;
        $agentSession->join_time  = $enterSession ? date('Y-m-d H:i:s') : null;
        $agentSession->leave_time = !$enterSession ? date('Y-m-d H:i:s') : null;

        return $agentSession->save() ? ['result' => 'ok'] : ['error' => ''];
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

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><action></action>');
        $xml->addAttribute('type', $did->name);
        $params = $xml->addChild('parameters');

        $actionParameters = $did->actionParameters()->joinParamTable()->get();
        foreach ($actionParameters as $param) {
            $node = $params->addChild('parameter', $param->parameter_value);
            $node->addAttribute('name', $param->name);
        }


        return $xml->asXML();
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
