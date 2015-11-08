<?php

namespace App\Models;

use Auth;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class App extends BaseModel
{
    const APP_KEYS_EXPIRE_DAYS = 5;
    use RevisionableTrait, SoftDeletes;

    protected $table = 'app';

    protected $fillable = [
        'name',
        'alias',
        'email',
        'password',
        'account_id'
    ];

    public function users()
    {
        return $this->hasMany('App\Models\AppUser', 'app_id');
    }

    public function key()
    {
        return $this->hasOne('App\Models\AppKey', 'app_id');
    }

    public function createApp($attributes, $user = null)
    {
        $user = $user ?: Auth::user();
        $user = DB::table('accounts')->select([
            'email',
            'password',
            'id AS account_id'
        ])->find($user->id);
        $this->fill((array)$user);
        $this->name  = $attributes['name'];
        $this->alias = $attributes['alias'];
        $appKey      = new AppKey();
        $expireDays  = self::APP_KEYS_EXPIRE_DAYS;

        return ($this->save() and $appKey->generateKeys($this, $expireDays));
    }

    public static function getApps($fields = [])
    {
        $user = Auth::user();
        $apps = App::whereAccountId($user->id);
        if ($fields)
            $apps->select($fields);
        return $apps;
    }

    public static function generateDashboardAppMenu($activeApp = '')
    {
        $apps = App::getApps()->get();
        $html = '';

        foreach ($apps as $app) {
            $html .= sprintf("
            <li class=\" %3\$s\">
                <a href=\"%1\$s\" title=\"%2\$s\">
                    <span>%2\$s</span>
                </a>
            </li>", url('app/dashboard/?app='.$app->id),
                    $app->name,
                    $app->id == $activeApp ? "active" : '');
        }

        return $html;

    }

    public function getManageAppMenu()
    {
        return [
            [
                'name' => 'API keys',
                'icon' => 'icon-key',
                'url'  => 'app-keys/index',
            ],
            [
                'name' => 'Users',
                'icon' => 'icon-user',
                'url'  => 'app-users/index',
            ],
            [
                'name' => 'View APP CDR',
                'icon' => 'icon-call-out',
                'url'  => 'app-cdr/index',
            ],
            [
                'name' => 'Number',
                'icon' => 'icon-screen-smartphone',
                'url'  => 'app-numbers/index',
            ],
            [
                'name' => 'Conference',
                'icon' => 'icon-earphones-alt',
                'url'  => 'app-conference/index',
            ],
        ];
    }
}
