<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class AppUsersController extends AppBaseController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $model    = $this->app;
        $title    = $model->name . ': Users';
        $subtitle = 'Manage users';

        return view('appUsers.index', compact('model', 'title', 'subtitle'));
    }

}
