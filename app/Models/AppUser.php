<?php

namespace App\Models;

use Auth;
use File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Venturecraft\Revisionable\RevisionableTrait;
use Webpatser\Uuid\Uuid;

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

    protected $hidden = ['password'];

    public static function createUser($params)
    {
        $params['uuid']     = Uuid::generate();
        $params['password'] = sha1($params['password']);

        return AppUser::create($params);
    }


    public function saveFile($file)
    {
        $user       = Auth::user();
        $path       = public_path() . '/upload';
        $extension  = $file->getClientOriginalExtension();
        $filename   = date('Y-m-d_H:i:s') . '_' . $user->id . ".$extension";
        $pathToFile = $path . '/' . $filename;
        $file->move($path, $filename);

        return $pathToFile;
    }

    public function isValidEmail($email)
    {
        $validator = Validator::make(
            ['email' => $email],
            ['email' => 'required|unique:users']
        );

        return !$validator->fails();
    }

    public function getDates()
    {
        return array(static::CREATED_AT, static::UPDATED_AT);
    }

    public function getUserAlias($clientId, $app)
    {
        return $app->name."-".$clientId."-".$this->email;
    }

}
