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

    public function conferences()
    {
        return $this->hasMany('App\Models\Conference', 'app_id');
    }

    public function queues()
    {
        return $this->hasMany('App\Models\Queue', 'app_id');
    }

    public function keys()
    {
        return $this->hasMany('App\Models\AppKey', 'app_id');
    }

    public function did()
    {
        return $this->hasMany('App\Models\DID', 'app_id')->whereNull('deleted_at');
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

    /**
     * App menu config
     * @return array
     */
    public function getManageAppMenu()
    {
        return [
            [
                'name' => 'APP Info',
                'icon' => 'icon-drawer',
                'url'  => 'app-data',
                'subMenu' => [
                    [
                        'name' => 'View APP CDR',
                        'icon' => 'icon-call-out',
                        'url'  => 'app-cdr/index',
                    ],
                    [
                        'name' => 'Daily usage',
                        'icon' => 'icon-calculator',
                        'url'  => 'app/daily-usage',
                    ],
                ]
            ],
            [
                'name'       => 'PBX',
                'icon'       => 'fa fa-phone',
                'url'        => 'app-pbx',
                'subMenu' => [
                    [
                        'name' => 'Conference log',
                        'icon' => 'fa fa-file-text',
                        'url'  => 'conferences/log',
                    ],
                    [
                        'name' => 'Agent session log',
                        'icon' => 'fa fa-file-text-o',
                        'url'  => 'queues/agent-log',
                    ],
                    [
                        'name' => 'Caller session log',
                        'icon' => 'fa fa-file-text-o',
                        'url'  => 'queues/caller-log',
                    ]
                ]
            ],
            [
                'name'       => 'SMS',
                'icon'       => 'icon-speech',
                'url'        => 'sms/inbox'
            ],
            [
                'name'       => 'API keys',
                'icon'       => 'icon-key',
                'url'        => 'app-keys/index',
                'labelCount' => 'keys'
            ],
            [
                'name'       => 'Manage DID',
                'icon'       => 'fa fa-phone-square',
                'url'        => 'did/index',
                'labelCount' => 'did'
            ],
            [
                'name'       => 'Conferences',
                'icon'       => 'fa fa-list',
                'url'        => 'conferences/index',
                'labelCount' => 'conferences'
            ],
            [
                'name'       => 'Queues',
                'icon'       => 'fa fa-list',
                'url'        => 'queues/index',
                'labelCount' => 'queues'
            ],
            [
                'name'       => 'Users',
                'icon'       => 'icon-user',
                'url'        => 'app-users/index',
                'labelCount' => 'users'
            ],
        ];
    }
}
