<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'type',
        'subject',
        'content'
    ];

    public function replaceMarkers($user)
    {
        $authUrl = sprintf('<a href="%s">%s</a>',
            url('activate/'.$user->activation_code),
            url('activate/'));

        $this->content = str_replace('__activation_url__', $authUrl, $this->content);
        $this->content = str_replace('__username__', $user->name, $this->content);
    }

}
