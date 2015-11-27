<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Models\DID;
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
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }
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
