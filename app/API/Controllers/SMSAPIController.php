<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\PlaySMSTrait;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\SMS;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;

class SMSAPIController extends Controller
{
    use Helpers, APIHelperTrait, PlaySMSTrait;

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

    /**
     * @SWG\Get(
     *     path="/api/sms/log",
     *     summary="Get SMS Log",
     *     tags={"sms"},
     *     @SWG\Parameter(
     *         description="APP User Id",
     *         name="userid",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Start from",
     *         name="start",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Parameter(
     *         description="End to",
     *         name="end",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Response(response="200", description="SMS Log"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getLog()
    {
        $this->setValidator([
            'userid' => 'required|exists:users,id,deleted_at,NULL',
            'start'  => 'required|date',
            'end'    => 'required|date',
        ]);

        $params['start'] = $this->request->start;
        $params['end']   = $this->request->end;

        return $this->getSMSLog($params);
    }

    /**
     * @SWG\Post(
     *     path="/api/sms/send",
     *     summary="Send SMS",
     *     tags={"sms"},
     *     @SWG\Parameter(
     *         description="APP User Id",
     *         name="user_id",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Text",
     *         name="text",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="SMS Sent"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postSend()
    {
        $this->setValidator([
            'user_id' => 'required|exists:users,id,deleted_at,NULL',
            'text'    => 'required|string'
        ]);

        $sms = new SMS();
        $sms->setUsers([$this->request->user_id]);
        $totalCost = $sms->getTotalCost();
        $appId     = $this->getAPPIdByAuthHeader();
        $user      = App::find($appId)->developer;

        if (!$user->hasSum($totalCost)) {
            $clientBalance = $user->getClientBalance();

            return $this->response->errorBadRequest('
                Could not send SMS: not enough balance.
                Total SMS cost: ' . $totalCost.'
                 Current balance: ' . $clientBalance . '
                 Client ID: ' . $user->clientId);
        } else {
            $totalSent = $sms->sendMessage($this->request->text);
            $user->deductSMSCost($totalSent);
            $result = $this->getSMSSentResult($totalSent);

            return $result;
        }

    }

    private function getSMSSentResult($totalSent)
    {
        $sentInfo = [];
        foreach ($totalSent as $data) {
            $sentInfo[] = sprintf('%s - %s', $data['user'], $data['error'] ?: 'sent');
        }

        $totalCost             = isset ($data['totalCost']) ? $data['totalCost'] : '';
        $sentInfo['totalCost'] = $totalCost;

        return $sentInfo;
    }
}
