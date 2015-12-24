<?php

namespace App\Models;



use App\Helpers\PlaySMSTrait;
use DB;

class SMS extends BaseModel
{
    use PlaySMSTrait;

    public $users = [];

    protected $countries = [];
    protected $totalCost = 0;

    protected $costByUser = [];

    const DEFAULT_SMS_COST = 10; //cents

    public function setUsers($users)
    {
        foreach ($users as $userId) {
            $user = AppUser::find($userId);
            $this->users[] = $user;
        }
    }

    public function getTotalCost()
    {
        $totalCost = 0;
        foreach ($this->users as $user) {
            $cost = $this->getSMSCostByUser($user);
            $totalCost += $cost;
            $this->costByUser[$user->email] = $cost;
        }

        $totalCost       = $totalCost ? $totalCost / 100 : 0;
        $this->totalCost = money_format('%i', $totalCost);

        return $this->totalCost;
    }

    public function getSMSCostByUser($user)
    {
        $cost      = self::DEFAULT_SMS_COST;
        $countryId = $this->getCountryIdByPhone($user->phone);

        $smsCost = DB::table('costs_sms')->whereCountryId($countryId)->first();
        if ($smsCost)
            $cost = $smsCost->cents_value;

        return $cost;
    }

    public function getCountryIdByPhone($userPhone)
    {
        $countryId = 0;
        $phone     = '';
        if (preg_match('(\d{1,3})', $userPhone, $phone))
            $phone = reset($phone);
        if ($phone) {
            $country = DB::table('countries')->whereCode($phone)->first();
            if ($country) {
                $this->countries[] = $country;
                $countryId = $country->id;
            }
        }

        return $countryId;
    }

    public function sendMessage($message)
    {
        $result = [];
        if ($this->totalCost) {
            foreach ($this->users as $user) {
                $number = preg_replace( '/[^0-9]/', '', $user->phone );
                $number = "+$number";
                $errorMessage = $this->sendSMS($number, $message);
                if (!$errorMessage)
                    $result[] = [
                        'user' => $user->email,
                        'cost' => isset($this->costByUser[$user->email]) ? $this->costByUser[$user->email] : null
                    ];
            }
        }

        return $result;
    }


}
