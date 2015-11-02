<?php

namespace App\Http\Controllers;

use App\Helpers\SidebarHelper;
use App\Models\App;
use Illuminate\Http\Request;
use App\Http\Requests;
use Redirect;

class AppBaseController extends Controller
{
    public $app;

    /**
     * Gets APP instance from $_GET parameter "app"
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $path        = $request->getPathInfo();
        $exceptPath  = array_flip($this->getExceptPaths());
        if (!isset($exceptPath[$path])) {
            $appId     = (int) $request->input('app');
            if ($appId != 0)
                $this->app = App::find($appId);
            if (!$this->app)
                Redirect::to('')->send();
        }
    }

    /**
     * Returns paths where APP instance is not needed
     */
    protected function getExceptPaths()
    {
        return [
            '/app/list',
            '/app/create',
            '/app/data'
        ];
    }
}
