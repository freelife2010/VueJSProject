<?php

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

class PaymentAPIController extends Controller
{
    use Helpers, APIHelperTrait;


    public function __construct()
    {
        $this->initAPI();
    }

    public function getBalance(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'userid' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        return 'ok';
    }

}
