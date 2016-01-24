<?php namespace App;

use App\Helpers\BillingTrait;
use App\Helpers\PaypalSDK;
use App\Models\BaseModel;
use Cache;
use Hash;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Bican\Roles\Traits\HasRoleAndPermission;
use Bican\Roles\Contracts\HasRoleAndPermission as HasRoleAndPermissionContract;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends BaseModel implements AuthenticatableContract, CanResetPasswordContract, HasRoleAndPermissionContract {

	use Authenticatable, CanResetPassword, HasRoleAndPermission, BillingTrait, SoftDeletes;

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
	protected $fillable = ['name', 'email', 'password', 'active', 'resent'];

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
		$user->active = 1;
		$user->activation_code = '';
		if($user->save()) {
			\Auth::login($user);
		}
        $this->fill($user->attributesToArray());
		return true;
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
		}

		return $this->clientBalance;
	}

	public function deductSMSCost($totalSent)
	{
		$totalDeduct = 0;
		foreach ($totalSent as $data) {
			$totalDeduct += $data['cost'];
		}
		$this->deductClientBalanceInBillingDB($totalDeduct);
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
				\Log::alert('Could not connect to Paypal go obtain balance');
			}
			if ($balanceData)
				$balance = reset($balanceData);
			Cache::put('balance_'.$this->id, $balance, 2);
		}

		return $balance. ' USD';
	}




}
