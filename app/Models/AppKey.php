<?php

namespace App\Models;

use DB;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class AppKey extends BaseModel
{
    protected $table = 'oauth_clients';

    protected $dates = [
        'created_at'
    ];

    public function app()
    {
        return $this->belongsTo('App\Models\App', 'app_id');
    }

    public function generateKeys($app, $expireDays, $scopes = [])
    {
        $expireDate        = date('Y-m-d H:i:s', strtotime("+ $expireDays days"));
        $this->app_id      = $app->id;
        $this->id          = Uuid::generate();
        $this->secret      = Str::random(60);
        $this->name        = $app->name;
        $this->expire_time = strtotime($expireDate);

        return ($this->save() and $this->setScopes($scopes));
    }

    public function isExpired()
    {
        return $this->expire_time < time();
    }

    public function setScopes($scopes)
    {
        $result = true;
        foreach ($scopes as $scope) {
            $scope = DB::table('oauth_scopes')->whereId($scope)->first();
            if ($scope and !DB::table('oauth_client_scopes')->insert(
                    [
                        'client_id' => $this->id,
                        'scope_id' => $scope->id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ))  $result = false;
        }

        return $result;
    }

    public function getScopes()
    {
        $html   = '';
        $scopes = DB::table('oauth_client_scopes')->select(['oauth_scopes.description', 'oauth_scopes.id'])
                    ->join('oauth_scopes', 'oauth_scopes.id', '=', 'oauth_client_scopes.scope_id')
                    ->whereClientId($this->id)->get();

        foreach ($scopes as $scope) {
            $html .= "$scope->description ($scope->id)<br/>";
        }

        return $html;
    }
}
