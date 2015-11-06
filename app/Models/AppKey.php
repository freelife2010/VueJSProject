<?php

namespace App\Models;

use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class AppKey extends BaseModel
{
    protected $table = 'oauth_clients';

    public function generateKeys($app, $expireDays)
    {
        $expireDate        = date('Y-m-d H:i:s', strtotime("+ $expireDays days"));
        $this->app_id      = $app->id;
        $this->id          = Uuid::generate();
        $this->secret      = Str::random(60);
        $this->name        = $app->name;
        $this->expire_time = strtotime($expireDate);

        return $this->save();
    }

    public function isExpired()
    {
        return $this->expire_time < time();
    }
}
