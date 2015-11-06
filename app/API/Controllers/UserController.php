<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;

class UserController extends Controller
{
    use Helpers, APIHelperTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        return $this->defaultResponse(AppUser::all()->all());
    }

    public function postIndex()
    {
        return $this->defaultResponse(AppUser::all()->all());
    }

}
