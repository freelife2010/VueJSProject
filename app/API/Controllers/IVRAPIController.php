<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Models\App;
use App\Models\IVR;
use App\User;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class IVRAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;


    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/ivr/list",
     *     summary="IVR",
     *     tags={"ivr"},
     *     @SWG\Response(response="200", description="IVR List"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getList()
    {
        $appId     = $this->getAPPIdByAuthHeader();
        $developer = App::findOrFail($appId)->developer;

        $ivr = IVR::whereAccountId($developer->id)->get();

        return $this->defaultResponse(['entities' => $ivr]);
    }

    /**
     * @SWG\Post(
     *     path="/api/ivr/add",
     *     summary="Add IVR",
     *     tags={"ivr"},
     *     @SWG\Parameter(
     *         description="IVR name",
     *         name="name",
     *         required=true,
     *         in="formData",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="IVR alias",
     *         name="alias",
     *         required=true,
     *         in="formData",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="IVR Parameter",
     *         name="parameter",
     *         in="formData",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Created IVR"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postAdd()
    {
        $this->setValidator([
            'name' => 'required|string',
            'alias' => 'required|string'
        ]);

        $appId     = $this->getAPPIdByAuthHeader();
        $developer = App::findOrFail($appId)->developer;

        $params               = $this->request->all();
        $params['account_id'] = $developer->id;

        $ivr = IVR::create($params);

        return $this->defaultResponse(['entities' => $ivr]);
    }

    /**
     * @SWG\Post(
     *     path="/api/ivr/delete",
     *     summary="Delete IVR",
     *     tags={"ivr"},
     *     @SWG\Parameter(
     *         description="IVR alias",
     *         name="alias",
     *         required=true,
     *         in="formData",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postDelete() {
        $this->setValidator([
            'alias' => 'required|string'
        ]);

        $result = IVR::whereAlias($this->request->alias)->delete();

        return $this->defaultResponse(['result' => ['deleted' => $result]]);
    }

}
