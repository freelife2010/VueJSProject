<?php

namespace App\Models;

use Auth;
use DB;

class App extends BaseModel
{
    protected $table = 'app';

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_id'
    ];

    public function users()
    {
        return $this->hasMany('App\Models\AppUser', 'app_id');
    }

    public function createApp($attributes)
    {
        $user = Auth::user();
        $user = DB::table('accounts')->select([
            'email',
            'password',
            'id AS account_id'
        ])->find($user->id);
        $this->fill((array) $user);
        $this->name = $attributes['name'];
        $this->presence = 1;
        $this->secret = '';
        $this->token  = '';

        return $this->save();
    }

    public static function getApps($fields = [])
    {
        $user = Auth::user();
        $apps = App::whereAccountId($user->id);
        if ($fields)
            $apps->select($fields);
        return $apps;
    }

    public static function generateAppMenu()
    {
        $apps = App::getApps()->get();
        $html = '';

        foreach ($apps as $app) {
            $html .= sprintf("
            <li class=\" \">
                <a href=\"%1\$s\" title=\"%2\$s\">
                    <span>%2\$s</span>
                </a>
            </li>", url('app/dashboard/'.$app->id),
                    $app->name);
        }

        return $html;

    }
}
