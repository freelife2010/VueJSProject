<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\PlaySMSTrait;
use App\Http\Controllers\Controller;
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

        return $this->getSMSLog();
    }
}
