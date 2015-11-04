<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class AppUser extends BaseModel
{
    use RevisionableTrait;

    public function app()
    {
        return $this->belongsTo('App\Models\App', 'app_id');
    }

    protected $table = 'users';
    protected $fillable = [
        'app_id',
        'name',
        'password',
        'email',
        'phone'
    ];

}
