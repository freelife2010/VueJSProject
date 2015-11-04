<?php

namespace App\API\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;

class UserController extends Controller
{
    use Helpers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = AppUser::all();
        return $this->response->array($users);
    }

}
