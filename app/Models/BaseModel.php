<?php

namespace App\Models;

use App\Models\ApiClient\GuzzleClient;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Support\Facades\URL;

class BaseModel extends Model
{

    /**
     * Returns date in "dd.mm.YY" format
     * @param string $field DB property to use
     * @return \DateTime|string
     */
    public function getProperDate($field)
    {
        $date  = new \DateTime($this->$field);
        if (!$this->$field
        or $date->getTimestamp() < 0)
            $date = '';
        else $date = $date->format('d.m.Y');

        return $date;
    }

    public function getDefaultActionButtons($controller_url, $urls = [])
    {
        $this->getActionUrls($urls, $controller_url);

        return $this->getButtonsHtml($urls);
    }

    public function getActionButtonsWithAPP($controller, $app)
    {
        $urls     = [];
        $getParam = '?app=' . $app->id;
        $this->getActionUrls($urls, $controller, $getParam);

        return $this->getButtonsHtml($urls);
    }

    protected function getButtonsHtml($urls)
    {
        $html = sprintf('
                    <a href="%s"
                       data-target="#myModal"
                       data-toggle="modal"
                       title="Edit"
                       class="btn btn-success btn-sm" >
                        <span class="fa fa-pencil"></span></a>
                ', URL::to($urls['edit']));
        $html .= sprintf('
                    <a href="%s"
                       data-target="#myModal"
                       data-toggle="modal"
                       title="Remove"
                       class="btn btn-danger btn-sm" >
                        <span class="fa fa-remove"></span></a>
                ', URL::to($urls['delete']));

        return $html;
    }

    public function generateButton($options)
    {
        $html = '';
        $html .= sprintf('
                <a href="%1$s"
                   data-target="#myModal"
                   data-toggle="modal"
                   title="%5$s"
                   class="btn %4$s btn-sm" >
                    <span class="%3$s"> %2$s</span></a>
            ',
            URL::to($options['url']),
            $options['name'],
            $options['icon'],
            $options['class'],
            $options['title']);

        return $html;
    }

    protected function getActionUrls(&$urls, $controller_url, $getParams = '') {

        if (empty($urls['edit']))
            $urls['edit'] = '/'.$controller_url.'/edit/'.$this->id . $getParams;
        if (empty($urls['delete']))
            $urls['delete'] = '/'.$controller_url.'/delete/'.$this->id . $getParams;
    }
}
