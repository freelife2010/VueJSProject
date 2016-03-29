<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use App\Http\Controllers\Controller;
use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\App;
use App\Models\AppUser;
use Config;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Webpatser\Uuid\Uuid;

class UserController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    public function __construct()
    {
        $this->initAPI();
        $this->scopes('users');
    }

    /**
     * @SWG\Get(
     *     path="/api/users",
     *     summary="List APP users",
     *     tags={"users"},
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     * )
     */
    public function getUsers()
    {
        $response = [
            'entities' => $this->getUserData()->get()
        ];
        return $this->defaultResponse($response);
    }

    /**
     * @SWG\Post(
     *     path="/api/users",
     *     summary="Create APP user(s)",
     *     tags={"users"},
     *      @SWG\Parameter(
     *         description="App user`s email",
     *         in="formData",
     *         name="username",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="App user's password",
     *         in="formData",
     *         name="password",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Returns created user(s)"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function createUsers()
    {
        $rules     = $this->getUserCreationInputRules($this->request);
        $user      = AppUser::whereEmail($this->request->username)->first();
        if ($user) return $this->response->errorInternal('The username has already been taken');
        $this->setValidator($rules);
        $appId    = $this->getAPPIdByAuthHeader();
        $response = $this->createUsersAndGetResponse($this->request, $appId);

        return $response;
    }

    private function createUsersAndGetResponse($request, $appId)
    {
        $input    = $request->input();
        if (!isset($input['phone']))
            $input['phone'] = '';
        $response = $this->makeErrorResponse('Failed to create user');
        if ($this->isMultiDimensionalArray($input)) {
            $entities = [];
            foreach ($input as $userParams) {
                if (!is_array($userParams)) continue;
                $params['username'] = $userParams['username'];
                $params['email']    = $userParams['username'];
                $params['phone']    = $userParams['phone'];
                $params['password'] = $userParams['password'];
                $params['app_id']   = $appId;

                $user       = $this->createSingleUser($params);
                $entities[] = $user;
            }
            $response = $this->defaultResponse(['entities' => $entities]);
        } else {
            $params['username'] = $input['username'];
            $params['email']    = $input['username'];
            $params['password'] = $input['password'];
            $params['phone']    = $input['phone'];
            $params['app_id']   = $appId;

            $user     = $this->createSingleUser($params);
            if ($user)
                $response = $this->defaultResponse(['entities' => $user]);
        }

        return $response;
    }

    private function createSingleUser($params)
    {
        $params['name']   = $params['username'];
        $params['email']  = $params['username'];
        if ($user = AppUser::createUser($params)) {
            $user->raw_password = $params['password'];
            $this->dispatch(new StoreAPPUserToBillingDB($user, $user->app));
            $this->dispatch(new StoreAPPUserToChatServer($user));
            $user = $this->getUserData()->whereId($user->id)->first();
            $user->billing_alias = $user->getUserAlias();
            unset($user->app);
            unset($user->country_id);
            unset($user->country);
            unset($user->tech_prefix);

        }

        return $user;
    }

    private function getUserData()
    {
        $appId     = $this->getAPPIdByAuthHeader();
        return $this->getEntities('AppUser', [
            'id as user_id',
            'app_id',
            'country_id',
            'tech_prefix',
            'email as username',
            'activated',
            'created_at as created',
            'updated_at as modified'
        ])->whereAppId($appId);
    }

    private function getUserCreationInputRules($request)
    {
        $rules      = [
            'username'   => 'required|email',
            'password'   => 'required'
        ];
        $input      = $request->input();
        if ($this->isMultiDimensionalArray($input)) {
            $rules = [];
            foreach($input as $key => $val)
            {
                if (!is_array($val)) continue;
                $rules[$key.'.username'] = 'required|email|unique:users,email';
                $rules[$key.'.password'] = 'required';
            }
        }

        return $rules;
    }

    /**
     * @SWG\Get(
     *     path="/api/users/{email}",
     *     summary="Get user info",
     *     tags={"users"},
     *      @SWG\Parameter(
     *         description="App user`s email",
     *         in="path",
     *         name="email",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="User info"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param $username
     * @return
     */
    public function getUserInfo($username)
    {
        $user = $this->getUserData()->whereEmail($username)->first();
        $response = $this->makeErrorResponse('Cannot find user');
        if ($user)
            $response = $this->defaultResponse($user->toArray());

        return $response;
    }

    /**
     * @SWG\Get(
     *     path="/api/sip-password",
     *     summary="Get SIP user's password",
     *     tags={"sip"},
     *      @SWG\Parameter(
     *         description="App user id",
     *         in="query",
     *         name="userid",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="App user`s billing alias",
     *         in="query",
     *         name="billing_alias",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Sip password"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getSipPassword()
    {
        $this->setValidator([
            'billing_alias' => 'required_without:userid',
            'userid'        => 'required_without:billing_alias|exists:users,id,deleted_at,NULL'
        ]);

        if ($this->request->has('userid')) {
            $user = AppUser::find($this->request->userid);
            $billingAlias = $user->getUserAlias();
        } else $billingAlias = $this->request->billing_alias;

        $username   = Misc::filterNumbers($billingAlias);
        $resourceIp = $this->getFluentBilling('resource_ip')->whereUsername($username)->first();

        if ($resourceIp)
            return $resourceIp->password;
        else return $this->response->error('Could not find user', 400);

    }

    /**
     * @SWG\Get(
     *     path="/api/sipuser/list",
     *     summary="List SIP users",
     *     tags={"sip"},
     *     @SWG\Parameter(
     *         description="App user id",
     *         in="query",
     *         name="userid",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="List of SIP users")
     * )
     */
    public function getSipUserList()
    {
        $this->setValidator([
            'userid'   => 'required|exists:users,id,deleted_at,NULL'
        ]);

        $user     = AppUser::find($this->request->userid);
        $resource = $this->getResourceByAliasFromBillingDB($user->getUserAlias());
        $entities = [];
        if ($resource) {
            $entities = $this->getFluentBilling('resource_ip')
                        ->whereResourceId($resource->resource_id)->get();
        }

        return $this->defaultResponse(['entities' => $entities]);
    }


    /**
     * @SWG\Post(
     *     path="/api/sipuser/add",
     *     summary="Create SIP user",
     *     tags={"sip"},
     *     @SWG\Parameter(
     *         description="App user id",
     *         in="formData",
     *         name="userid",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Password",
     *         in="formData",
     *         name="password",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postSipUserAdd()
    {
        $this->setValidator([
            'userid'   => 'required|exists:users,id,deleted_at,NULL',
            'password' => 'required'
        ]);

        $user     = AppUser::findOrFail($this->request->userid);
        $alias    = $user->getUserAlias();
        $resource = $this->getResourceByAliasFromBillingDB($alias);
        $username = rand(100,999).Misc::filterNumbers($alias);
        $inserted = false;
        if ($resource) {
            $inserted = $this->getFluentBilling('resource_ip')
                ->insert([
                    'resource_id' => $resource->resource_id,
                    'username'    => $username,
                    'password'    => $this->request->password
                ]);
            if ($inserted)
                $inserted = $this->getFluentBilling('resource_ip')
                            ->whereUsername($username)->first();
        }

        return $this->defaultResponse(['result' => $inserted]);

    }

    /**
     * @SWG\Post(
     *     path="/api/sipuser/delete",
     *     summary="Delete SIP user",
     *     tags={"sip"},
     *     @SWG\Parameter(
     *         description="App user id",
     *         in="formData",
     *         name="userid",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="SIP Username",
     *         name="sip_user",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postSipUserDelete()
    {
        $this->setValidator([
            'userid'   => 'required|exists:users,id,deleted_at,NULL',
            'sip_user' => 'required'
        ]);

        $user     = AppUser::find($this->request->userid);
        $resource = $this->getResourceByAliasFromBillingDB($user->getUserAlias());
        $result   = false;
        if ($resource) {
            $result = $this->getFluentBilling('resource_ip')
                ->whereUsername($this->request->sip_user)->delete();
        }

        return $this->defaultResponse(['result' => ['deleted' => $result]]);

    }


}
