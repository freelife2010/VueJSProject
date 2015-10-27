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
}
