<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 05.11.15
 * Time: 17:18
 */

namespace App\API\Controllers;


use App\API\APIHelperTrait;
use App\Jobs\StoreAPPToBillingDB;
use App\Jobs\StoreAPPToChatServer;
use App\Models\App;
use App\User;
use DB;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class PublicAPIController extends Controller{
    use Helpers, APIHelperTrait;

    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Post(
     *     path="/api/app/create",
     *     summary="Create new APP",
     *     tags={"app"},
     *     @SWG\Parameter(
     *         description="App name",
     *         in="formData",
     *         name="name",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Developer Account ID",
     *         in="formData",
     *         name="account_id",
     *         required=true,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         description="Auth sign",
     *         in="formData",
     *         name="sign",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param Request $request
     * @return
     */
    public function createAPP(Request $request)
    {
        $this->setValidator([
            'name'       => 'required|unique:app',
            'account_id' => 'required',
            'sign'       => 'required'
        ]);
        $sign      = $this->getSign($request);
        if ($sign != $request->input('sign'))
             $this->response->errorUnauthorized('Incorrect sign, access denied');
        else return $this->makeApp($request);
    }

    private function makeApp($request)
    {
        $accountId  = $request->input('account_id');
        $name       = $request->input('name');
        $alias      = Str::random(30);
        $user       = User::find($accountId);
        $app        = new App();
        $attributes = compact('name', 'alias');
        $response   = ['error' => 'APP creation failed'];
        if ($user and $app->createApp($attributes, $user)) {
            $this->dispatch(new StoreAPPToBillingDB($app, $user));
            $this->dispatch(new StoreAPPToChatServer($app, $user));
            $key = $app->keys->pop();
            $response = [
                'app_uuid'   => $key->id,
                'app_secret' => $key->secret,
                'duration'   => App::APP_KEYS_EXPIRE_DAYS,
            ];

        }

        return $this->defaultResponse($response);
    }

    public function getTokenInfo()
    {
        $response    = $this->makeErrorResponse('Failed to get access token info');
        $accessToken = $this->getAccessTokenFromHeader();
        $accessToken = DB::table('oauth_access_tokens')
                        ->select(['created_at as created', 'expire_time as expiration'])
                        ->whereId($accessToken)->first();
        if ($accessToken)
            $response = $this->defaultResponse(['entities' => (array) $accessToken]);

        return $response;

    }

}