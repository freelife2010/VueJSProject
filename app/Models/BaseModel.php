<?php

namespace App\Models;

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

    public function getDefaultActionButtons($controller_url, $urls = [], $exclude = [])
    {
        $exclude = array_flip($exclude);
        $html    = '';
        $this->getActionUrls($urls, $controller_url);

        if (!isset($exclude['edit']))
            $html .= sprintf('
                        <a href="%s"
                           data-target="#myModal"
                           data-toggle="modal"
                           title="Edit"
                           class="btn btn-success btn-sm" >
                            <span class="glyphicon glyphicon-pencil"></span></a>
                    ', URL::to($urls['edit']));
        if (!isset($exclude['delete'])) {
            $html .= sprintf('
                        <a href="%s"
                           data-target="#myModal"
                           data-toggle="modal"
                           title="Remove"
                           class="btn btn-danger btn-sm" >
                            <span class="glyphicon glyphicon-remove"></span></a>
                    ', URL::to($urls['delete']));
        }

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

    protected function getActionUrls(&$urls, $controller_url) {

        if (empty($urls['edit']))
            $urls['edit'] = '/'.$controller_url.'/edit/'.$this->id;
        if (empty($urls['delete']))
            $urls['delete'] = '/'.$controller_url.'/delete/'.$this->id;
    }
}
