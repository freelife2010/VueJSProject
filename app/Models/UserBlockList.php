<?php

namespace App\Models;

use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use Auth;
use Cache;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Validator;
use Venturecraft\Revisionable\RevisionableTrait;
use Webpatser\Uuid\Uuid;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Contracts\Billable as BillableContract;

class UserBlockList extends BaseModel implements BillableContract
{
    use RevisionableTrait, BillingTrait, Billable;

    protected $clientId;
    public $timestamps = true;

//    public function app()
//    {
//        return $this->belongsTo('App\Models\App', 'app_id');
//    }

//    public function country()
//    {
//        return $this->belongsTo('App\Models\Country');
//    }

//    public function dids()
//    {
//        return $this->hasMany('App\Models\DID', 'owned_by')->whereNull('deleted_at');
//    }

    protected $table = 'user_block_list';

    protected $fillable = [
        'user_id',
        'blocked_user_id'
    ];

    protected $dates = [
        'created_at'
    ];

    public static function blockUser($blockerId, $userId)
    {
        return self::insertGetId([
            'user_id' => $blockerId,
            'blocked_user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function isUserBlocked($userId, $blockerId)
    {
        $row = self::select('id')
            ->where('blocked_user_id', $userId)
            ->where('user_id', $blockerId)
            ->first();
        return isset($row->id) ? true : false;
    }

    // protected $hidden = ['password', 'tech_prefix'];
    /*
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
            $sipUser  = [
                'resource_id' => $resource->resource_id,
                'username'    => $username,
                'password'    => $password,
                'reg_srv_ip'  => '158.69.203.191',
                'reg_type'    => 1,
                'reg_status'  => 1,
                'direction'   => 0
            ];
            if ($resource) {
                $inserted = $this->getFluentBilling('resource_ip')
                    ->insert($sipUser);
                $sipResourceId = $this->insertGetIdToBillingDB("
                                        insert into resource ( alias, egress )
                                        values (?, 't') RETURNING resource_id",
                    [$username], 'resource_id');
                $userProduct   = $this->getUserProductId();

                $this->getFluentBilling('resource_ip')->insert([
                    'resource_id' => $sipResourceId,
                    'ip'          => '158.69.203.191',
                    'port'        => 5060
                ]);
            }

            return $sipUser;
        }

        public function getUserProductId()
        {
            $clientName = Misc::filterNumbers($this->getUserAlias());

            $userProduct = $this->getFluentBilling('product')->whereName($clientName)->first();

            if (!$userProduct)
                $userProduct = $this->insertGetIdToBillingDB("insert into product (name,code_type)
                                      values (?,0) RETURNING product_id",
                    [$clientName], 'product_id');
            else $userProduct = $userProduct->product_id;

            return $userProduct;

        }

        public function getDefaultSipAccount()
        {
            $alias = Misc::filterNumbers($this->getUserAlias());
            $sip   = $this->getFluentBilling('resource_ip')->whereUsername($alias)->first();

            return $sip ? $sip->username : '';
        }

        public static function getUserStatuses()
        {
            $statuses = ['Inactive', 'Active'];
            asort($statuses);

            return $statuses;
        }

        public function getSipAccounts()
        {
            $fields = [
                'resource_ip_id',
                'username',
                'password',
                'reg_status'
            ];

            $resource    = $this->getResourceByAliasFromBillingDB($this->getUserAlias());
            $sipAccounts = [];
            if ($resource) {
                $sipAccounts = $this->getFluentBilling('resource_ip')
                    ->select($fields)
                    ->whereResourceId($resource->resource_id)->get();
            }

            return new Collection($sipAccounts);
        }

        public function getClientBalance()
        {
            $balance = Cache::get('balance_' . $this->id);
            if ($balance === null) {
                $this->clientId = $this->getClientIdByAliasFromBillingDB($this->getUserAlias());
                $balance        = $this->getClientBalanceFromBillingDB($this->clientId);
                if ($balance === false)
                    $this->createClientBalance();
            }

            $balance = round($balance, 2);

            Cache::add('balance_' . $this->id, $balance, 10);

            return $balance;
        }

        public function createClientBalance()
        {
            $this->insertToBillingDB("
                        insert into c4_client_balance (client_id,balance,ingress_balance)
                        values (?,?,?)",
                [$this->clientId, 0, 0]);
        }

        public function addCredit($amount)
        {
            $clientId       = $this->getClientIdByAliasFromBillingDB($this->getUserAlias());
            $currentBalance = $this->getClientBalanceFromBillingDB($clientId) * 100;
            $newSum         = $currentBalance + $amount;
            $newSum         = $newSum ? $newSum / 100 : 0;
            $newSum         = money_format('%i', $newSum);

            $db = $this->getFluentBilling('c4_client_balance');

            $this->clearBalanceCache();

            return $db->whereClientId($clientId)->update(['balance' => $newSum]);
        }

        public function clearBalanceCache()
        {
            Cache::forget('balance_'.$this->id);
        }
    */

}
