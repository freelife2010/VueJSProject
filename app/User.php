<?php namespace App;

use App\Helpers\BillingTrait;
use App\Helpers\PaypalSDK;
use App\Models\BaseModel;
use Cache;
use Hash;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Bican\Roles\Traits\HasRoleAndPermission;
use Bican\Roles\Contracts\HasRoleAndPermission as HasRoleAndPermissionContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Contracts\Billable as BillableContract;


class User extends BaseModel implements AuthenticatableContract, CanResetPasswordContract, HasRoleAndPermissionContract, BillableContract {

	use Authenticatable, CanResetPassword, HasRoleAndPermission, BillingTrait, SoftDeletes, Billable;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'accounts';
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name',
		'email',
		'phone',
		'password',
		'active',
		'resent',
		'stripe_customer_id'
	];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];

	public $clientId = 0;
	protected $clientBalance = 0;

    public function apps() {
        return $this->hasMany('App\Models\App', 'account_id');
    }
    public function accountIsActive($code) {
		$user = User::where('activation_code', '=', $code)->first();
		if ($user) {
			$user->active = 1;
			$user->activation_code = '';
			if($user->save()) {
				\Auth::login($user);
			}
			$this->fill($user->attributesToArray());
			return true;
		} return false;
	}

    public function setPasswordAttribute($pass){

        $this->attributes['password'] = Hash::make($pass);

    }

    public function getFullNameAttribute()
    {
        return $this->first_name. ' ' . $this->last_name;
    }

	public function hasSum($sum)
	{
		$clientBalance = $this->getClientBalance();

		return $clientBalance >= $sum;
	}

	/**
	 * @return int
	 */
	public function getClientBalance()
	{
		if (!$this->clientBalance) {
			$this->clientId      = $this->getCurrentUserIdFromBillingDB($this);
			$this->clientBalance = $this->getClientBalanceFromBillingDB($this->clientId);
			if ($this->clientBalance === false)
				$this->createClientBalance();
		}

		$balance = round($this->clientBalance, 2);

		Cache::add('balance_'.$this->id, $balance, 10);

		return $balance;
	}

	public function createClientBalance()
	{
		$this->insertToBillingDB("
                    insert into c4_client_balance (client_id,balance,ingress_balance)
                    values (?,?,?)",
			[$this->clientId, 0, 0]);
	}

	public function deductSMSCost($totalSent)
	{
		$totalDeduct = 0;
		foreach ($totalSent as $data) {
			$totalDeduct += $data['cost'];
		}
		$this->deductClientBalanceInBillingDB($totalDeduct);
		$this->clearBalanceCache();
	}

	public function addCredit($amount)
	{
		$clientId 		= $this->getCurrentUserIdFromBillingDB($this);
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

	public function getBalance()
	{
		$balance = Cache::get('balance_'.$this->id, null);
		if (is_null($balance)) {
			$balance  = null;
			$paypal   = new PaypalSDK();
			try{
				$balanceData  = $paypal->call('GetBalance');
			} catch (\Exception $e) {
				$balanceData = [0.0];
			}
			if ($balanceData)
				$balance = reset($balanceData);
			Cache::put('balance_'.$this->id, $balance, 2);
		}

		return $balance. ' USD';
	}

	public function createStripeId($token)
	{
		$customer = $this->subscription()->createStripeCustomer($token, [
			'email' => $this->email
		]);
		$this->setStripeId($customer->id);
	}

	public function getAllAppsEgressIds()
	{
		$appAliases   = $this->apps->pluck('tech_prefix');
		$appResources = $this->getFluentBilling('resource')->whereIn('alias', $appAliases->all())->get();
		$appEgressIds = [];
		array_walk($appResources, function($resource) use (&$appEgressIds) {
			$appEgressIds[] = $resource->resource_id;
		});

		return $appEgressIds;
	}


}
