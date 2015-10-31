<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\Request;
use App\Http\Requests;
use Redirect;

class AppBaseController extends Controller
{
    public $app;

    function __construct(Request $request)
    {
        $appId     = (int) $request->input('app');
        if ($appId != 0)
            $this->app = App::find($appId);
        else Redirect::to('')->send();
    }
}
