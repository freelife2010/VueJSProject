<?php

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\User;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

class PaymentAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;


    public function __construct()
    {
        $this->initAPI();
    }

    public function getBalance(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'userid' => 'required|exists:users,id,deleted_at,NULL'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }
        $user = AppUser::find($request->userid);
        $user->setBillingDBAlias();

        $balance = 0;

        $clientId = $this->getCurrentUserIdFromBillingDB($user);
        if ($clientId)
            $balance = $this->getClientBalanceFromBillingDB($clientId, 'ingress_balance');

        return $balance;
    }

    public function postAddCredit(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'userid' => 'required|exists:users,id,deleted_at,NULL',
            'amount' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $user = AppUser::find($request->userid);
        $user->setBillingDBAlias();
        $clientId = $this->getCurrentUserIdFromBillingDB($user);
        if ($clientId)
            $this->storeClientPaymentInBillingDB($clientId, $request->amount, $request->remark);


    }

}
