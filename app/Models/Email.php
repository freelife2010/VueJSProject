<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'type',
        'subject',
        'content',
        'from_name',
        'from_address',
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_password'
    ];

    /**
     * Replaces e-mail markers with actual data
     * @param $user
     */
    public function replaceMarkers($user)
    {
        $authUrl = sprintf('<a href="%s">%s</a>',
            url('activate/'.$user->activation_code),
            url('activate/'));

        $this->content = str_replace('__activation_link__', $authUrl, $this->content);
        $this->content = str_replace('__username__', $user->name, $this->content);
    }

    public function setSMTPSettings()
    {
        if ($this->smtp_host and $this->smtp_port) {
            Config::set('mail.host', $this->smtp_host);
            Config::set('mail.port', $this->smtp_port);
        }

        if ($this->smtp_user and $this->smtp_password) {
            Config::set('mail.username', $this->smtp_user);
            Config::set('mail.password', $this->smtp_password);
        }
    }

}
