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

/**
 * This helper instantiates  with every view render of "layouts.default" layout
 * Helper is created in app/Providers/AppServiceProvider
 * Class SidebarHelper
 * @package App\Helpers
 */
class SidebarHelper {

    /**
     * This model is taken from app/Providers/AppServiceProvider
     * @var null
     */
    public $model;

    function __construct($model = null)
    {
        $this->model = $model;
    }

    public function getAppCount()
    {
        return count(App::getApps()->get());
    }

    public function generateDashboardAppMenu()
    {
        $activeApp = $this->getActiveApp();

        return App::generateDashboardAppMenu($activeApp);
    }

    public function generateManageAppMenu()
    {
        $html = '';
        if ($this->model) {
            $menuItems = $this->model->getManageAppMenu();

            $html     .= '<li class="nav-heading ">
                            <span data-localize="sidebar.heading.HEADER">Manage APP: '
                            . $this->model->name .'</span>
                          </li>';
            foreach ($menuItems as $menuItem) {
                $name       = $menuItem['name'];
                $icon       = $menuItem['icon'];
                $url        = $menuItem['url'];
                $labelCount = isset($menuItem['labelCount']) ? $menuItem['labelCount'] : '';
                $html .= $this->generateMenuItem($name, $url, $icon, $labelCount);
            }
        }

        return $html;
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

    protected function generateMenuItem($name, $url, $icon, $labelCount = false)
    {
        if ($labelCount)
            $printLabel = $this->getMenuLabel($labelCount);
        else $printLabel = '';
        $activeApp = $this->getActiveApp();
        return sprintf('
                <li class="%1$s">
                    <a href="%2$s" title="%4$s">
                        %5$s
                        <em class="%3$s"></em>
                        <span>%4$s</span>
                    </a>
                </li>',
                    Request::is(dirname($url).'*') ? 'active' : '',
                    url($url.'/?app='.$activeApp),
                    $icon,
                    $name,
                    $printLabel);
    }

    protected function getMenuLabel($labelCount)
    {
        return sprintf('
                        <div class="pull-right label label-info">
                            %s
                        </div>',
            count($this->model->$labelCount));
    }

    protected function getActiveApp()
    {
       return $this->model ? $this->model->id : 0;
    }

}