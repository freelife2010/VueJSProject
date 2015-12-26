<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;

class SMSAPIController extends Controller
{
    use Helpers, APIHelperTrait;

    public function __construct()
    {
        $this->initAPI();
        $this->scopes('sms');
    }

    public function postAddCredit(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'amount'   => 'required|numeric'
        ]);
    }
}
