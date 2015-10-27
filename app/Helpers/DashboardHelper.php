<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 27.10.15
 * Time: 20:03
 */

namespace App\Helpers;


use App\Models\App;

class DashboardHelper {

    public function getAppCount()
    {
        return count(App::getApps()->get());
    }

    public function generateAppMenu()
    {
        return App::generateAppMenu();
    }
}