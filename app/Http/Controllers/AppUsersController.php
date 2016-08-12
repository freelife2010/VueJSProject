<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Helpers\ExcelParser;
use App\Http\Requests;
use App\Http\Requests\AppUserRequest;
use App\Http\Requests\DeleteRequest;
use App\Http\Requests\UploadUsersRequest;
use App\Jobs\DeleteAPPUserFromBillingDB;
use App\Jobs\DeleteAPPUserFromChatServer;
use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\App;
use App\Models\AppUser;
use App\Models\DID;
use App\Models\Email;
use App\Models\UserBlockList;
use App\Models\UserFriendList;
use DB;
use Mail;
use Former\Facades\Former;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use URL;
use Yajra\Datatables\Datatables;
use App\Helpers\Misc;

class AppUsersController extends AppBaseController
{
    use BillingTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $APP = $this->app;
        $title = $APP->name . ': Users';
        $subtitle = 'Manage users';

        return view('appUsers.index', compact('APP', 'title', 'subtitle'));
    }

    public function getCreate()
    {
        $APP = $this->app;
        $title = 'Create new user';
        $statuses = AppUser::getUserStatuses();

        return view('appUsers.create_edit', compact('title', 'APP', 'statuses'));
    }

    public function postCreate(AppUserRequest $request)
    {
        $result = $this->getResult(true, 'Could not create user');
        $params = $request->input();

        if ($user = AppUser::createUser($params)) {
            $result = $this->getResult(false, 'User created successfully');
            $user->raw_password = $request->password;
            $this->dispatch(new StoreAPPUserToBillingDB($user, $user->app));
            $this->fillFriendToBillingDB($user->id, $user->id);

            $this->dispatch(new StoreAPPUserToChatServer($user));
        }

        return $result;
    }

    public function getEdit($id)
    {
        $title = 'Edit User';
        $model = AppUser::find($id);
        $APP = $this->app;
        $statuses = AppUser::getUserStatuses();
        unset($model->password);

        return view('appUsers.create_edit', compact('title', 'model', 'APP', 'statuses'));
    }

    public function postEdit(AppUserRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not edit user');
        $model = AppUser::find($id);
        $params = $request->input();
        $params['phone'] = str_replace('_', '', $params['phone']);
        $params['allow_outgoing_call'] = $request->has('allow_outgoing_call') ?: null;
        if ($model->fill($params)
            and $model->save()
        )
            $result = $this->getResult(false, 'User saved successfully');

        return $result;
    }

    public function getDelete($id)
    {
        $title = 'Delete user ?';
        $model = AppUser::find($id);
        $APP = $this->app;
        $url = Url::to('app-users/delete/' . $model->id);

        return view('appUsers.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        return '';
        $result = $this->getResult(true, 'Could not delete user');
        $model = AppUser::find($id);
        if ($model->delete()) {
            $this->dispatch(new DeleteAPPUserFromBillingDB($model));
            $this->dispatch(new DeleteAPPUserFromChatServer($model));
            $result = $this->getResult(false, 'User deleted');
        }

        return $result;
    }

    public function getData()
    {
        $users = AppUser::select([
            'id',
            'app_id',
            'tech_prefix',
            'country_id',
            'name',
            'email',
            'phone',
            'last_status'
        ])->whereAppId($this->app->id);

        return Datatables::of($users)
            ->edit_column('id', function ($user) {
                return $user->getUserAlias();
            })
            ->edit_column('last_status', function ($user) {
                return $user->last_status ? 'Active' : 'Inactive';
            })
            ->add_column('actions', function ($user) {
                $html = $user->getActionButtonsWithAPP('app-users', $this->app, ['delete']);
                $options = [
                    'url' => "app-user-payments/$user->id?app={$this->app->id}",
                    'name' => '',
                    'title' => 'View user payment history',
                    'icon' => 'fa fa-usd',
                    'class' => 'btn-info'
                ];
                $html .= $user->generateButton($options);
                $options = [
                    'url' => 'app-users/daily-usage/' . $user->id . '?app=' . $this->app->id,
                    'name' => '',
                    'title' => 'View daily usage',
                    'icon' => 'icon-calculator',
                    'class' => 'btn-default'
                ];
                $html .= $user->generateButton($options);

                return $html;
            })
            ->add_column('did', function ($user) {
                $dids = $user->dids;
                $html = '';
                foreach ($dids as $did) {
                    $html .= $did->did . '<br/>';
                }

                return $html;

            })
            ->add_column('balance', function ($user) {
                return $user->getClientBalance().'$';
            })
            ->make(true);
    }

    public function getImport()
    {
        $APP = $this->app;
        $title = 'Import users';

        return view('appUsers.import', compact('title', 'APP'));
    }

    public function postImport(UploadUsersRequest $request)
    {
        $model = new AppUser();
        $result = $this->getResult(true, 'Could not import users');
        $APP = App::find($request->input('app_id'));
        if ($request->hasFile('sheet_file')
            and $APP
        ) {
            $columns = [
                'email' => $request->input('email'),
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'phone' => $request->input('phone') ?: 'phone',
            ];
            $pathToFile = $model->saveFile($request->file('sheet_file'));
            $parser = new ExcelParser($model, $APP);
            $parser->run($pathToFile, $columns);
            $totalSaved = $parser->getTotalSaved();
            $errors = $parser->getErrors();
            if ($errors) {
                $errors = implode('<br/>', $errors);
                $result = $this->getResult(true, $errors);
            } else $result = $this->getResult(false, 'Users have been imported<br/>Total saved: ' . $totalSaved);
        }

        return $result;
    }

    public function getDailyUsage($id)
    {
        $APP = $this->app;
        $model = AppUser::find($id);
        if (!$model)
            return redirect()->back();
        $title = $model->name . ': Daily usage';
        $subtitle = 'View daily usage';

        return view('appUsers.daily_usage', compact('title', 'subtitle', 'APP', 'model'));
    }

    public function getDailyUsageData($id)
    {
        $fields =
            'report_time,
             duration,
             sum(ingress_bill_time)/60 as min,
             sum(ingress_call_cost+lnp_cost) as cost';

        $model = AppUser::find($id);
        $clientAlias = $model->getUserAlias();
        $dailyUsage = new Collection();
        $resource = $this->getResourceByAliasFromBillingDB($clientAlias);
        if ($resource)
            $dailyUsage = $this->getFluentBilling('cdr_report')->selectRaw($fields)
                ->whereIngressClientId($resource->resource_id)->groupBy('report_time', 'duration');

        return Datatables::of($dailyUsage)->make(true);
    }

    public function getCallerIdInputs()
    {
        $dids = DID::whereAppId($this->app->id)
            ->whereNull('deleted_at')
            ->get()->lists('did', 'did');

        $html = Former::select('caller_id')->addOption('Outside number', 0)
            ->options($dids)->placeholder('Select DID')->label('Number');

        return $html;
    }

    public function getSip()
    {
        $APP = $this->app;
        $title = $APP->name . ': SIP Accounts';
        $appUsers = $APP->users()->lists('email', 'id');
        $subtitle = 'Manage SIP Accounts';

        return view('appUsers.sip_accounts', compact('APP', 'title', 'subtitle', 'appUsers'));
    }

    public function getSipAccountsData(Request $request)
    {
        $appUser = AppUser::find($request->app_user_id);
        $sipAccounts = $appUser->getSipAccounts();

        $datatables = Datatables::of($sipAccounts);
        $this->addSipActionButtons($datatables, $appUser);

        return $datatables->make(true);
    }

    public function addSipActionButtons($datatables, $appUser)
    {
        $datatables->add_column('actions', function ($sip) use ($appUser) {
            $options = [
                'url' => 'app-users/edit-sip-account/' . $sip->resource_ip_id . '?app=' . $this->app->id,
                'name' => '',
                'title' => 'Edit',
                'icon' => 'fa fa-pencil',
                'class' => 'btn-success',
                'modal' => true
            ];
            $html = $appUser->generateButton($options);

            $options = [
                'url' => 'app-users/delete-sip-account/' . $sip->resource_ip_id . '?app=' . $this->app->id,
                'name' => '',
                'title' => 'Delete',
                'icon' => 'fa fa-remove',
                'class' => 'btn-danger',
                'modal' => true
            ];

            $html .= $appUser->generateButton($options);

            return $html;

        });
    }

    public function getCreateSipAccount()
    {
        $APP = $this->app;
        $title = 'Create new SIP account';
        $appUsers = $APP->users()->lists('email', 'id');

        return view('appUsers.create_edit_sip_account', compact('title', 'APP', 'appUsers'));
    }

    public function postCreateSipAccount(Request $request)
    {
        $this->validate($request, [
            'app_user_id' => 'required',
            'password' => 'required'
        ]);
        $result = $this->getResult(true, 'Could not create SIP account');
        $appUser = AppUser::find($request->app_user_id);
        if ($appUser->createSipAccount($request->password))
            $result = $this->getResult(false, 'Sip account has been created');

        return $result;
    }

    public function getEditSipAccount($id)
    {
        $APP = $this->app;
        $title = 'Edit SIP account';
        $model = $this->getFluentBilling('resource_ip')
            ->whereResourceIpId($id)->first();
        $model->id = $model->resource_ip_id;
        $appUsers = $APP->users()->lists('email', 'id');

        return view('appUsers.create_edit_sip_account',
            compact('title', 'APP', 'appUsers', 'model'));
    }

    public function postEditSipAccount(Request $request, $id)
    {
        $values = [
            'password' => $request->password
        ];
        $result = $this->getResult(true, 'Could not edit SIP account');
        $update = $this->getFluentBilling('resource_ip')
            ->whereResourceIpId($id)->update(
                $values
            );
        if ($update)
            $result = $this->getResult(false, 'SIP account changed successfully');

        return $result;
    }

    public function getDeleteSipAccount($id)
    {
        $APP = $this->app;
        $title = 'Delete SIP account';
        $model = $this->getFluentBilling('resource_ip')
            ->whereResourceIpId($id)->first();
        $model->id = $model->resource_ip_id;
        $url = Url::to('app-users/delete-sip-account/' . $id);

        return view('appUsers.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDeleteSipAccount(Request $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete SIP account');
        $delete = $this->getFluentBilling('resource_ip')
            ->whereResourceIpId($id)->delete();
        if ($delete)
            $result = $this->getResult(false, 'SIP account deleted successfully');

        return $result;
    }

    public function getSipAccountsHtml($userId)
    {
        $appUser = AppUser::find($userId);

        $options   = $appUser->getSipAccounts()->pluck('username', 'username');
        $parameter = DB::table('did_action_parameters')
            ->select(['name', 'id'])->whereName('APP user id')->first();
        $html      = Former::label('SIP User');
        $html .= Former::select("parameters[$parameter->id]")->options($options, 2)
            ->placeholder('SIP User')->label('')->required();

        return $html;
    }

    public function getFriendList()
    {
        $APP = $this->app;
        $title = $APP->name . ': Friends List';
        $appUsers = $APP->users()->lists('email', 'id');
        $subtitle = 'Manage Friends';

//        UserFriendList::truncate();
//        UserBlockList::truncate();

        return view('appUsers.friends', compact('APP', 'title', 'subtitle', 'appUsers'));
    }

    public function getFriendBlockList()
    {
        $APP = $this->app;
        $title = $APP->name . ': Friends List';
        $appUsers = $APP->users()->lists('email', 'id');
        $subtitle = 'Manage Friends';

        return view('appUsers.friends_block_list', compact('APP', 'title', 'subtitle', 'appUsers'));
    }

    public function getFriendBlockListData(Request $request)
    {
        $appUserId = $request->appUserId;
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

        $users->where('user_block_list.user_id', '=', $appUserId);

        return Datatables::of($users)
            ->edit_column('id', function ($user) use ($appUserId) {
                return $user->getUserAlias();
            })
            ->edit_column('name', function ($user) use ($appUserId) {
                return $user->name;
            })
            ->edit_column('phone', function ($user) use ($appUserId) {
                return $user->phone;
            })
            ->edit_column('email', function($user) use ($appUserId) {
                return $user->email;
            })
            ->edit_column('last_status', function ($user) use ($appUserId) {
                return 'Blocked';
            })
            ->make(true);
    }

    public function getFriendListData(Request $request)
    {
        $appUserId = $request->appUserId;
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
//        if ($appUserId) {
            $users->where('user_friend_list.user_id', '=', $appUserId)
                ->orWhere('user_friend_list.user_sent_to_id', '=', $appUserId);
            $users->join('user_friend_list', 'user_friend_list.user_sent_to_id', '=', 'users.id');
//        }

        return Datatables::of($users)
            ->edit_column('id', function ($user) use ($appUserId) {
                return $appUserId == $user->friend_user_id ? $user->getUserAlias() : AppUser::find($user->friend_user_id)->getUserAlias();
            })
            ->edit_column('name', function ($user) use ($appUserId) {
                return $appUserId == $user->friend_user_id ? $user->name : AppUser::find($user->friend_user_id)->name;
            })
            ->edit_column('phone', function ($user) use ($appUserId) {
                return $appUserId == $user->friend_user_id ? $user->phone : AppUser::find($user->friend_user_id)->phone;
            })
            ->edit_column('email', function($user) use ($appUserId) {
                return $appUserId == $user->friend_user_id ? $user->email : AppUser::find($user->friend_user_id)->email;
            })
            ->edit_column('last_status', function ($user) use ($appUserId) {
                return $appUserId == $user->friend_user_id
                    ? ($user->accepted ? 'Accepted' : 'Pending')
                    : ($user->accepted ? 'Accepted' : 'Waiting For Accept');
            })
            ->add_column('actions', function ($user) use ($appUserId) {
                $html = '';
                $classDisabled = $appUserId ? '' : 'disabled';
                if ($appUserId == $user->friend_user_id) {
                    // do something...
                    if ($user->accepted) {
                        $options = [
                            'url' => "app-users/decline-friend-request/{$user->id}/{$appUserId}?app={$this->app->id}",
                            'name' => '',
                            'title' => 'Decline Friend Request',
                            'icon' => 'fa fa-remove',
                            'class' => "btn-warning $classDisabled",
                            'modal' => true
                        ];
                        $html .= $user->generateButton($options);
                        $options = [
                            'url' => "app-users/block-user/{$user->id}/{$appUserId}?app={$this->app->id}",
                            'name' => '',
                            'title' => 'Block User',
                            'icon' => 'fa fa-ban',
                            'class' => "btn-danger $classDisabled",
                            'modal' => true
                        ];
                        $html .= $user->generateButton($options);
                    }
                } else {
                    //&& !$user->accepted
                    if (!$user->accepted) {
                        $options = [
                            'url' => "app-users/accept-friend-request/{$appUserId}/{$user->friend_user_id}/?app={$this->app->id}",
                            'name' => '',
                            'title' => 'Accept Friend Request',
                            'icon' => 'fa fa-check',
                            'class' => "btn-info $classDisabled",
                            'modal' => true
                        ];
                        $html .= $user->generateButton($options);
                    }
                        $options = [
                            'url' => "app-users/decline-friend-request/{$appUserId}/{$user->friend_user_id}/?app={$this->app->id}",
                            'name' => '',
                            'title' => 'Decline Friend Request',
                            'icon' => 'fa fa-remove',
                            'class' => "btn-warning $classDisabled",
                            'modal' => true
                        ];
                        $html .= $user->generateButton($options);
                        $options = [
                            'url' => 'app-users/block-user/' . $appUserId . '/' . $user->friend_user_id . '?app=' . $this->app->id,
                            'name' => '',
                            'title' => 'Block User',
                            'icon' => 'fa fa-ban',
                            'class' => "btn-danger $classDisabled",
                            'modal' => true
                        ];
                        $html .= $user->generateButton($options);
//                    }
                }

                return $html;
            })
            ->make(true);
    }

    public function getBlockUser($appUserId, $appRecipientUserId)
    {
        $APP = $this->app;
        $title = 'Are you sure you want to block user?';
        $model = [];
        $actionUrl = Url::to('app-users/block-user/' . $appUserId . '/' . $appRecipientUserId);
        $submitLabel = 'Block';

        return view('appUsers.friend_action', compact('title', 'actionUrl', 'submitLabel', 'appUserId', 'appRecipientUserId'));
    }

    public function postBlockUser($appUserId, $appBlockUserId)
    {
        $result = $this->getResult(true, 'The User is already blocked');
        if (!UserBlockList::isUserBlocked($appBlockUserId, $appUserId)) {
            UserFriendList::declineFriendRequest($appBlockUserId, $appUserId);
            UserFriendList::declineFriendRequest($appUserId,$appBlockUserId);
            $this->removeFriendsFromBillingDB($appUserId, $appBlockUserId);
            $this->removeFriendsFromBillingDB($appBlockUserId, $appUserId);
            UserBlockList::blockUser($appUserId, $appBlockUserId);
            $result = $this->getResult(false, 'The User is successfully blocked');
        }
        return $result;
    }


    public function getUnblockUser($appUserId, $appBlockUserId)
    {
        // todo...
    }

    public function getSendFriendRequest()
    {
        $APP = $this->app;
        $title = 'Send New Friend Request';
        $actionUrl = Url::to('app-users/send-friend-request/');
        $showAppUserSelect = true;
        $appUsers = $APP->users()->lists('email', 'id');
        $submitLabel = 'Send';

        return view('appUsers.friend_action', compact('title', 'APP', 'appUsers', 'actionUrl', 'submitLabel', 'showAppUserSelect'));
    }

    public function postSendFriendRequest(Request $request)
    {
        $appUserId = $request->get('app_user_id');
        $appRecipientUserId = $request->get('app_recipient_user_id');

        $result = $this->getResult(true, 'This User can not send request to himself');
        if ($appUserId != $appRecipientUserId) {
            $result = $this->getResult(true, 'This User was added to the block list');
            if (!UserBlockList::isUserBlocked($appUserId,$appRecipientUserId)) {
                $result = $this->getResult(true, 'Sorry. Your request is already sent');
                if (!UserFriendList::isRequestAlreadySent($appUserId, $appRecipientUserId)) {
                    $result = $this->getResult(true, 'Sorry. This User is already in your friend list');
                    if (!UserFriendList::isFriend($appUserId, $appRecipientUserId)) {
                        UserFriendList::sendFriendRequest($appUserId, $appRecipientUserId);
                        $result = $this->getResult(false, 'Your friend request is successfully sent');
                    }
                }
            }
        }

        return $result;
    }

    public function getViewFriendRequest()
    {
        $APP = $this->app;
        $title = $APP->name . ': Friends List';
        $appUsers = $APP->users()->lists('email', 'id');
        $subtitle = 'Manage Friends';

        return view('appUsers.view_friends', compact('APP', 'title', 'subtitle', 'appUsers'));
    }

    public function getAcceptFriendRequest($appUserId, $appRecipientUserId)
    {
        $APP = $this->app;
        $title = 'Are you sure you want to accept a friend request?';
        $actionUrl = Url::to('app-users/accept-friend-request/' . $appUserId . '/' . $appRecipientUserId);
        $submitLabel = 'Accept';

        return view('appUsers.friend_action', compact('title', 'actionUrl', 'submitLabel', 'appUserId', 'appRecipientUserId'));
    }

    public function postAcceptFriendRequest(Request $request)
    {
        $appUserId = $request->get('app_user_id');
        $appRecipientUserId = $request->get('app_recipient_user_id');

        UserFriendList::acceptFriendRequest($appRecipientUserId,$appUserId);
        $this->fillFriendToBillingDB($appUserId, $appRecipientUserId);
        $this->fillFriendToBillingDB($appRecipientUserId, $appUserId);

        $result = $this->getResult(false, 'The Friend request is successfully accepted');

        return $result;
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

    public function getDeclineFriendRequest($appUserId, $appRecipientUserId)
    {
        $APP = $this->app;
        $title = 'Are you sure you want to decline a friend request?';
        $actionUrl = Url::to('app-users/decline-friend-request/' . $appUserId . '/' . $appRecipientUserId);
        $submitLabel = 'Decline';

        return view('appUsers.friend_action', compact('title', 'actionUrl', 'submitLabel', 'appUserId', 'appRecipientUserId'));
    }

    public function postDeclineFriendRequest(Request $request)
    {
        $appUserId = $request->get('app_user_id');
        $appRecipientUserId = $request->get('app_recipient_user_id');
        UserFriendList::declineFriendRequest($appUserId,$appRecipientUserId);
        UserFriendList::declineFriendRequest($appRecipientUserId, $appUserId);
        $this->removeFriendsFromBillingDB($appUserId, $appRecipientUserId);
        $this->removeFriendsFromBillingDB($appRecipientUserId, $appUserId);

        $result = $this->getResult(false, 'The Friend request is successfully declined');

        return $result;
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

    public function getAddCredit()
    {
        $APP = $this->app;
        $title = $APP->name . ': Add Credit';
        $appUsers = $APP->users()->lists('email', 'id');
        $subtitle = 'Add Credit';

        return view('appUsers.add_credit', compact('APP', 'title', 'subtitle', 'appUsers'));
    }

    public function postAddCredit(Request $request)
    {
        $this->validate($request, [
            'app_user_id' => 'required',
            'amount' => 'required|integer',
            'remark' => 'required'
        ]);

        $user = AppUser::find($request->app_user_id);

        $response = $this->getResult(false, 'Failed');
        $clientId = $this->getClientIdByAliasFromBillingDB($user->getUserAlias());
        if ($clientId) {
            $this->storeClientPaymentInBillingDB($clientId, $request->amount, $request->remark);
            $response = $this->getResult(false, 'Added');
        }

        return $response;
    }
}
