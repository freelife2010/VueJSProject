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
                $html .= $this->generateMenuItem($menuItem);
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

    protected function generateMenuItem($menuItem)
    {
        $url         = $menuItem['url'];
        $name        = $menuItem['name'];
        $icon        = $menuItem['icon'];
        $activeApp   = $this->getActiveApp();
        $labelCount  = isset($menuItem['labelCount']) ? $menuItem['labelCount'] : '';
        $subMenu     = isset($menuItem['subMenu']) ? $menuItem['subMenu'] : '';
        $subMenu     = $this->generateSubMenu($subMenu, $url, $activeApp);
        $printLabel  = $this->getMenuLabel($labelCount);

        $menuItem = $this->getMenuItemHtml($name, $url, $icon, $activeApp, $printLabel, $subMenu);

        return $menuItem;
    }

    protected function getMenuItemHtml(
        $name,
        $url,
        $icon,
        $activeApp,
        $printLabel = false,
        $subMenu = false
    ) {
        return sprintf('
                <li class="%1$s">
                    <a href="%2$s" title="%4$s" %6$s>
                        %5$s
                        <em class="%3$s"></em>
                        <span>%4$s</span>
                    </a>
                    %7$s
                </li>
                ',
            Request::is($url . '*') ? 'active' : '',
            $subMenu ? "#$url" : url($url . '/?app=' . $activeApp),
            $icon,
            $name,
            $printLabel,
            $subMenu ? 'data-toggle="collapse"' : '',
            $subMenu ?: '');
    }

    protected function generateSubMenu($subMenu, $parentId, $activeApp)
    {
        $html = '';
        if ($subMenu) {
            $htmlItems = '';
            foreach ($subMenu as $menuItem) {
                $htmlItems .= $this->getMenuItemHtml(
                    $menuItem['name'],
                    $menuItem['url'],
                    $menuItem['icon'],
                    $activeApp);
            }
            $html .= sprintf('
                        <ul id="%1$s" class="nav sidebar-subnav collapse">
                            %2$s
                        </ul>
                        ',  $parentId,
                            $htmlItems);
        }

        return $html;
    }

    protected function getMenuLabel($labelCount)
    {
        return $labelCount ? sprintf('
                        <div class="pull-right label label-info">
                            %s
                        </div>',
            count($this->model->$labelCount)) : '';
    }

    protected function getActiveApp()
    {
       return $this->model ? $this->model->id : 0;
    }

}