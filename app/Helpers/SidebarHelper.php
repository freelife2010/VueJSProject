<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 27.10.15
 * Time: 20:03
 */

namespace App\Helpers;


use App\Models\App;
use Request;

class SidebarHelper {

    public $model;

    function __construct($model = null)
    {
        $this->model = $model;
    }

    public function getAppCount()
    {
        return count(App::getApps()->get());
    }

    public function generateAppMenu()
    {
        $activeApp = $this->model ? $this->model->id : 0;

        return App::generateAppMenu($activeApp);
    }

    public function isActive($path)
    {
        $path = $this->getPathWithId($path);
        return Request::is("$path*") ? "active" : '';
    }

    protected function getPathWithId($path)
    {
        $path .= $this->model ? '/'.$this->model->id : '';

        return $path;
    }
}