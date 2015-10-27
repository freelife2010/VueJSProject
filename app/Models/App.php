<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends BaseModel
{
    protected $table = 'app';

    public function users()
    {
        return $this->hasMany('App\AppUser', 'app_id');
    }
}
