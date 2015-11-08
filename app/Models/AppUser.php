<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class AppUser extends BaseModel
{
    use SoftDeletes, RevisionableTrait;

    public function app()
    {
        return $this->belongsTo('App\Models\App', 'app_id');
    }

    protected $table = 'users';
    protected $fillable = [
        'app_id',
        'uuid',
        'name',
        'password',
        'email',
        'phone'
    ];

}
