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

class UserFriendList extends BaseModel implements BillableContract
{
    use RevisionableTrait, BillingTrait, Billable;

    protected $clientId;
    public $timestamps = true;


    protected function appUser()
    {
        return $this->belongsTo('App\Models\AppUser');
    }

    protected $table = 'user_friend_list';

    protected $fillable = [
        'user_id',
        'user_sent_to_id',
        'accepted'
    ];

    protected $dates = [
        'created_at'
    ];

    public static function declineFriendRequest($userId, $recipientUserId)
    {
        return self::where('user_id', $userId)
            ->where('user_sent_to_id', $recipientUserId)
            ->delete();
    }

    public static function acceptFriendRequest($userId, $recipientUserId)
    {
        return self::where('user_id', $userId)
            ->where('user_sent_to_id', $recipientUserId)
            ->update([
                'accepted' => 1
            ]);
    }

    public static function sendFriendRequest($userId, $recipientUserId)
    {
        return self::insertGetId([
            'user_id' => $userId,
            'user_sent_to_id' => $recipientUserId,
            'accepted' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function isRequestAlreadySent($userId, $recipientUserId)
    {
        $row = self::select('id')
            ->where('user_id', $userId)
            ->where('user_sent_to_id', $recipientUserId)
            ->where('accepted', 0)
            ->first();
        return isset($row->id) ? true : false;
    }

    public static function isFriend($userId, $recipientUserId)
    {
        $row = self::select('id')
            ->where('user_id', $userId)
            ->where('user_sent_to_id', $recipientUserId)
            ->where('accepted', 1)
            ->first();
        return isset($row->id) ? true : false;
    }
}
