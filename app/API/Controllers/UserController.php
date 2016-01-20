<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\Misc;
use App\Http\Controllers\Controller;
use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\AppUser;
use Config;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Webpatser\Uuid\Uuid;

class UserController extends Controller
{
    use Helpers, APIHelperTrait;

    public function __construct()
    {
        $this->initAPI();
        $this->scopes('users');
    }

    public function getUsers()
    {
        $response = [
            'entities' => $this->getUserData()->get()
        ];
        return $this->defaultResponse($response);
    }

    public function createUsers()
    {
        $rules     = $this->getUserCreationInputRules($this->request);
        $validator = $this->makeValidator($this->request, $rules);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }
        $appId    = $this->getAPPIdByAuthHeader();
        $response = $this->createUsersAndGetResponse($this->request, $appId);

        return $response;
    }

    private function createUsersAndGetResponse($request, $appId)
    {
        $input    = $request->input();
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
            $this->dispatch(new StoreAPPUserToBillingDB($user, $user->app));
            $this->dispatch(new StoreAPPUserToChatServer($user));
            $user = $this->getUserData()->whereId($user->id)->first();

        }

        return $user;
    }

    private function getUserData()
    {
        return $this->getEntities('AppUser', [
            'uuid as user_uuid',
            'email as username',
            'activated',
            'created_at as created',
            'updated_at as modified'
        ]);
    }

    private function getUserCreationInputRules($request)
    {
        $rules      = [
            'username'   => 'required|email|unique:users,email',
            'password'   => 'required',
            'phone'      => 'required'
        ];
        $input      = $request->input();
        if ($this->isMultiDimensionalArray($input)) {
            $rules = [];
            foreach($input as $key => $val)
            {
                if (!is_array($val)) continue;
                $rules[$key.'.username'] = 'required|email|unique:users,email';
                $rules[$key.'.password'] = 'required';
                $rules[$key.'.phone'] = 'required';
            }
        }

        return $rules;
    }


    public function getUserInfo($username)
    {
        Authorizer::getResourceOwnerId();
        $user = $this->getUserData()->whereEmail($username)->first();
        $response = $this->makeErrorResponse('Cannot find user');
        if ($user)
            $response = $this->defaultResponse($user->toArray());

        return $response;
    }


}
