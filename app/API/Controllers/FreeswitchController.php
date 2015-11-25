<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Models\DID;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class FreeswitchController extends Controller
{
    use Helpers, APIHelperTrait;


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCallHandler(Request $request)
    {
        $all = $request->all();
        $validator = $this->makeValidator($request, [
            'dnis' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        return $this->getDIDXmlResponse($request->dnis);
    }

    protected function getDIDXmlResponse($did)
    {
        $did = DID::whereDid($did)->action()->first();

    }


}
