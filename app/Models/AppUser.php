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
        'phone',
        'tech_prefix'
    ];

    protected $hidden = ['password', 'tech_prefix'];

    public static function createUser($params)
    {
        $params['uuid']        = Uuid::generate();
        $params['password']    = sha1($params['password']);
        $params['tech_prefix'] = self::generateTechPrefix();

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
        return [static::CREATED_AT, static::UPDATED_AT];
    }

    public function getUserAlias($clientId, $app)
    {
        return $app->name."-".$clientId."-".$this->email;
    }

    public static function generateTechPrefix() {
        $number = mt_rand(0, 99999999);

        // call the same function if the barcode exists already
        if (self::TechPrefixExists($number)) {
            return self::generateTechPrefix();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public static function TechPrefixExists($number) {
        // query the database and return a boolean
        // for instance, it might look like this in Laravel
        return AppUser::whereTechPrefix($number)->exists();
    }

}
