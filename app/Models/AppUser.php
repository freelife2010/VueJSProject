<?php

namespace App\Models;

use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use Auth;
use File;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Venturecraft\Revisionable\RevisionableTrait;
use Webpatser\Uuid\Uuid;

class AppUser extends BaseModel
{
    use SoftDeletes, RevisionableTrait, BillingTrait;

    public function app()
    {
        return $this->belongsTo('App\Models\App', 'app_id');
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public function dids()
    {
        return $this->hasMany('App\Models\DID', 'owned_by')->whereNull('deleted_at');
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
        'allow_outgoing_call',
        'caller_id',
        'country_id'
    ];

    protected $hidden = ['password', 'tech_prefix'];

    public static function createUser($params)
    {
        $params['uuid']        = Uuid::generate();
        $params['password']    = sha1($params['password']);
        $params['tech_prefix'] = Misc::generateUniqueId();

        if (!isset($params['allow_outgoing_call'])) {
            $params['caller_id'] = 0;
        }

        $smsModel             = new SMS();
        $countryId            = $smsModel->getCountryIdByPhone($params['phone']);
        $params['country_id'] = $countryId;
        $params['phone']      = str_replace('_', '', $params['phone']);

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
            ['email' => 'required|unique:users,email,0,id,deleted_at,NULL']
        );

        return !$validator->fails();
    }

    public function getDates()
    {
        return [static::CREATED_AT, static::UPDATED_AT];
    }

    public function getUserAlias()
    {
        $country   = $this->country;
        $countryId = $country ? $country->equivalent : '000';

        return $this->app->tech_prefix . '-' . $countryId . '-' . $this->tech_prefix;
    }

    public function createSipAccount($password)
    {
        $alias    = $this->getUserAlias();
        $resource = $this->getResourceByAliasFromBillingDB($alias);
        $username = Misc::filterNumbers($alias) . rand(100, 999);
        $inserted = false;
        if ($resource) {
            $inserted = $this->getFluentBilling('resource_ip')
                ->insert([
                    'resource_id' => $resource->resource_id,
                    'username'    => $username,
                    'password'    => $password,
                    'reg_srv_ip'  => '158.69.203.191',
                    'reg_type'    => 1,
                    'reg_status'  => 1,
                    'direction'   => 0
                ]);
        }

        return $inserted;
    }


}
