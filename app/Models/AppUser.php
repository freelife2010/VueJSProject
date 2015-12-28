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

    public function dids()
    {
        return $this->hasMany('App\Models\DID', 'owned_by');
    }

    protected $table = 'users';

    protected $fillable = [
        'app_id',
        'uuid',
        'name',
        'password',
        'email',
        'phone',
        'tech_prefix',
        'user_id',
        'caller_id'
    ];

    protected $hidden = ['password', 'tech_prefix'];

    public static function createUser($params)
    {
        $params['uuid']        = Uuid::generate();
        $params['password']    = sha1($params['password']);
        $params['tech_prefix'] = self::generateUniqueId();
        $params['user_id']     = self::generateUniqueId(9999999999, 'user_id');
        $params['caller_id']   = (isset($params['caller_id_custom']) and $params['caller_id_custom']) ?
                                        $params['caller_id_custom'] :
                                        $params['caller_id'];
        $params['caller_id'] = $params['caller_id'] ?: 0;

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

    public static function generateUniqueId($digits = 99999999, $field = 'tech_prefix') {
        $number = mt_rand(0, $digits);

        // call the same function if the barcode exists already
        if (self::IdExists($number, $field)) {
            return self::generateUniqueId();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public static function IdExists($number, $field) {
        // query the database and return a boolean
        // for instance, it might look like this in Laravel
        return AppUser::where($field, '=', $number)->exists();
    }

}
