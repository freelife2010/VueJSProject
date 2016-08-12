<?php

namespace App\API\Controllers;
use App\Helpers\BillingTrait;
use App\API\APIHelperTrait;
use App\Models\AppUser;
use App\Models\UserBlockList;
use App\Models\UserFriendList;
use Dingo\Api\Contract\Http\Request;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Process;

class FriendAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    public function __construct()
    {
        $this->initAPI();
    }


    /**
     * @SWG\Post(
     *     path="/api/friend/send-friend-request",
     *     summary="Send APP user friend request(s)",
     *     tags={"friends"},
     *      @SWG\Parameter(
     *         description="First App user`s id",
     *         in="formData",
     *         name="first_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="Second App user`s id",
     *         in="formData",
     *         name="second_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postSendFriendRequest()
    {
        $appUserId = $this->request->first_app_user_id;
        $appRecipientUserId = $this->request->second_app_user_id;

        $message = 'This User can not send request to himself';
        if ($appUserId != $appRecipientUserId) {
            $message = 'This User was added to the block list';
            if (!UserBlockList::isUserBlocked($appUserId,$appRecipientUserId)) {
                $message = 'Sorry. Your request is already sent';
                if (!UserFriendList::isRequestAlreadySent($appUserId, $appRecipientUserId)) {
                    $message = 'Sorry. This User is already in your friend list';
                    if (!UserFriendList::isFriend($appUserId, $appRecipientUserId)) {
                        UserFriendList::sendFriendRequest($appUserId, $appRecipientUserId);
                        $message = 'Your friend request is successfully sent';
                    }
                }
            }
        }

        $response = [
            'message' => $message
        ];
        return $this->defaultResponse($response);
    }

    /**
     * @SWG\Post(
     *     path="/api/friend/accept-friend-request",
     *     summary="Accept APP user friend request(s)",
     *     tags={"friends"},
     *      @SWG\Parameter(
     *         description="First App user`s id",
     *         in="formData",
     *         name="first_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="Second App user`s id",
     *         in="formData",
     *         name="second_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postAcceptFriendRequest()
    {
        $appUserId = $this->request->first_app_user_id;
        $appRecipientUserId = $this->request->second_app_user_id;

        UserFriendList::acceptFriendRequest($appRecipientUserId,$appUserId);
        $this->fillFriendToBillingDB($appUserId, $appRecipientUserId);
        $this->fillFriendToBillingDB($appRecipientUserId, $appUserId);

        $message = 'The Friend request is successfully accepted';

        $response = [
            'message' => $message
        ];
        return $this->defaultResponse($response);
    }

    /**
     * @SWG\Post(
     *     path="/api/friend/block-user",
     *     summary="Block APP user friend request(s)",
     *     tags={"friends"},
     *      @SWG\Parameter(
     *         description="First App user`s id",
     *         in="formData",
     *         name="first_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="Second App user`s id",
     *         in="formData",
     *         name="second_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postBlockUser()
    {
        $appUserId = $this->request->first_app_user_id;
        $appRecipientUserId = $this->request->second_app_user_id;

        $message = 'The User is already blocked';
        if (!UserBlockList::isUserBlocked($appRecipientUserId, $appUserId)) {
            UserFriendList::declineFriendRequest($appRecipientUserId, $appUserId);
            UserFriendList::declineFriendRequest($appUserId,$appRecipientUserId);
            $this->removeFriendsFromBillingDB($appUserId, $appRecipientUserId);
            $this->removeFriendsFromBillingDB($appRecipientUserId, $appUserId);
            UserBlockList::blockUser($appUserId, $appRecipientUserId);
            $message = 'The User is successfully blocked';
        }

        $response = [
            'message' => $message
        ];
        return $this->defaultResponse($response);
    }

    /**
     * @SWG\Post(
     *     path="/api/friend/decline-friend-request",
     *     summary="Decline APP user friend request(s)",
     *     tags={"friends"},
     *      @SWG\Parameter(
     *         description="First App user`s id",
     *         in="formData",
     *         name="first_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *      @SWG\Parameter(
     *         description="Second App user`s id",
     *         in="formData",
     *         name="second_app_user_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postDeclineFriendRequest()
    {
        $appUserId = $this->request->first_app_user_id;
        $appRecipientUserId = $this->request->second_app_user_id;

        UserFriendList::declineFriendRequest($appUserId,$appRecipientUserId);
        UserFriendList::declineFriendRequest($appRecipientUserId, $appUserId);
        $this->removeFriendsFromBillingDB($appUserId, $appRecipientUserId);
        $this->removeFriendsFromBillingDB($appRecipientUserId, $appUserId);

        $message = 'The Friend request is successfully declined';

        $response = [
            'message' => $message
        ];
        return $this->defaultResponse($response);
    }


    /**
     * @SWG\Get(
     *     path="/api/friend/block-list",
     *     summary="List APP user friends",
     *     tags={"friends"},
     *     @SWG\Parameter(
     *         description="APP User Id",
     *         name="app_user_id",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     * )
     * @return bool|mixed
     */
    public function getBlockList()
    {
        $userId = $this->request->app_user_id;

        $users = AppUser::select([
            'users.id',
            'users.app_id',
            'users.tech_prefix',
            'users.country_id',
            'users.name',
            'users.email',
            'users.phone',
            'users.last_status',
        ])->join('user_block_list', 'user_block_list.blocked_user_id', '=', 'users.id');

        $users->where('user_block_list.user_id', '=', $userId);

        $response = [
            'blockList' => $users->get()
        ];
        return $this->defaultResponse($response);
    }






    /**
     * @SWG\Get(
     *     path="/api/friend/list",
     *     summary="List APP user friends",
     *     tags={"friends"},
     *     @SWG\Parameter(
     *         description="APP User Id",
     *         name="app_user_id",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="400", description="Validation failed"),
     *     @SWG\Response(response="401", description="Auth required"),
     * )
     * @return bool|mixed
     */
    public function getList()
    {
        $userId = $this->request->app_user_id;

        $users = AppUser::select([
            'users.id',
            'users.app_id',
            'users.tech_prefix',
            'users.country_id',
            'users.name',
            'users.email',
            'users.phone',
            'users.last_status',
            'user_friend_list.user_sent_to_id',
            'user_friend_list.user_id as friend_user_id',
            'user_friend_list.accepted',
        ]);

        $users->where('user_friend_list.user_id', '=', $userId)
            ->orWhere('user_friend_list.user_sent_to_id', '=', $userId);
        $users->join('user_friend_list', 'user_friend_list.user_sent_to_id', '=', 'users.id');

        $response = [
            'friendList' => $users->get()
        ];
        return $this->defaultResponse($response);
    }


    private function fillFriendToBillingDB($firstUserId, $secondUserId)
    {
        $firstUserAlias = AppUser::find($firstUserId)->getUserAlias();
        $firstUserAlias = str_replace('-', '', $firstUserAlias);
        $secondUserAlias = AppUser::find($secondUserId)->getUserAlias();

        $productId = $this->selectFromBillingDB("
                                select product_id from product
                                where name = ?", [$firstUserAlias]);

        $productId = $this->fetchField($productId, 'product_id');
        $resourceId = $this->selectFromBillingDB("
                                select resource_id from resource
                                where alias = ?", ["{$secondUserAlias}_P2P"]);
        $resourceId = $this->fetchField($resourceId, 'resource_id');
        $secondUserAlias = str_replace('-', '', $secondUserAlias);
        $productItemId = $this->insertGetIdToBillingDB("insert into product_items (product_id, digits)
                                  values (?,?) RETURNING item_id",
            [$productId, $secondUserAlias], 'item_id');
        $this->insertToBillingDB("insert into product_items_resource (item_id, resource_id)
                                  values (?,?) ",
            [$productItemId, $resourceId]);

    }

    private function removeFriendsFromBillingDB($firstUserId, $secondUserId)
    {
        $firstUserAlias = AppUser::find($firstUserId)->getUserAlias();
        $firstUserAlias = str_replace('-', '', $firstUserAlias);
        $secondUserAlias = AppUser::find($secondUserId)->getUserAlias();

        $productId = $this->selectFromBillingDB("
                                select product_id from product
                                where name = ?", [$firstUserAlias]);

        $productId = $this->fetchField($productId, 'product_id');
        $resourceId = $this->selectFromBillingDB("
                                select resource_id from resource
                                where alias = ?", ["{$secondUserAlias}_P2P"]);
        $resourceId = $this->fetchField($resourceId, 'resource_id');
        $secondUserAlias = str_replace('-', '', $secondUserAlias);

        $productItemId = $this->selectFromBillingDB("
                                select item_id from product_items
                                where product_id = ? and digits = ?", [$productId, $secondUserAlias]);
        $productItemId = $this->fetchField($productItemId, 'item_id');

        $this->getFluentBilling('product_items')->where('product_id', '=', $productId)->where('digits', '=', $secondUserAlias)->delete();
        $this->getFluentBilling('product_items_resource')->where('item_id', '=', $productItemId)->where('resource_id', '=', $resourceId)->delete();

    }


}
