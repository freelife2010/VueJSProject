<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class AppUser extends BaseModel
{
    use RevisionableTrait;

    protected $table = 'users';
    protected $fillable = [
        'app_id',
        'name',
        'password',
        'email',
        'phone'
    ];

}
