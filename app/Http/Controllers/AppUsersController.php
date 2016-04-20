<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Helpers\ExcelParser;
use App\Helpers\Misc;
use App\Http\Requests;
use App\Http\Requests\AppUserRequest;
use App\Http\Requests\DeleteRequest;
use App\Http\Requests\UploadUsersRequest;
use App\Jobs\DeleteAPPUserFromBillingDB;
use App\Jobs\DeleteAPPUserFromChatServer;
use App\Jobs\DeleteAPPUserToChatServer;
use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\App;
use App\Models\AppUser;
use App\Models\DID;
use Former\Facades\Former;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use URL;
use Yajra\Datatables\Datatables;

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
        $APP      = $this->app;
        $title    = $APP->name . ': Users';
        $subtitle = 'Manage users';

        return view('appUsers.index', compact('APP', 'title', 'subtitle'));
    }

    public function getCreate()
    {
        $APP      = $this->app;
        $title    = 'Create new user';
        $statuses = AppUser::getUserStatuses();

        return view('appUsers.create_edit', compact('title', 'APP', 'statuses'));
    }

    public function postCreate(AppUserRequest $request)
    {
        $result = $this->getResult(true, 'Could not create user');
        $params = $request->input();

        if ($user = AppUser::createUser($params)) {
            $result             = $this->getResult(false, 'User created successfully');
            $user->raw_password = $request->password;
            $this->dispatch(new StoreAPPUserToBillingDB($user, $user->app));
            $this->dispatch(new StoreAPPUserToChatServer($user));
        }

        return $result;
    }

    public function getEdit($id)
    {
        $title    = 'Edit User';
        $model    = AppUser::find($id);
        $APP      = $this->app;
        $statuses = AppUser::getUserStatuses();
        unset($model->password);

        return view('appUsers.create_edit', compact('title', 'model', 'APP', 'statuses'));
    }

    public function postEdit(AppUserRequest $request, $id)
    {
        $result                        = $this->getResult(true, 'Could not edit user');
        $model                         = AppUser::find($id);
        $params                        = $request->input();
        $params['phone']               = str_replace('_', '', $params['phone']);
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
        $APP   = $this->app;
        $url   = Url::to('app-users/delete/' . $model->id);

        return view('appUsers.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        return '';
        $result = $this->getResult(true, 'Could not delete user');
        $model  = AppUser::find($id);
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
                $options = [
                    'url'   => 'app-users/daily-usage/' . $user->id . '?app=' . $this->app->id,
                    'name'  => '',
                    'title' => 'View daily usage',
                    'icon'  => 'icon-calculator',
                    'class' => 'btn-default'
                ];
                $html    = $user->generateButton($options);
                $html .= $user->getActionButtonsWithAPP('app-users', $this->app, ['delete']);

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
            ->make(true);
    }

    public function getImport()
    {
        $APP   = $this->app;
        $title = 'Import users';

        return view('appUsers.import', compact('title', 'APP'));
    }

    public function postImport(UploadUsersRequest $request)
    {
        $model  = new AppUser();
        $result = $this->getResult(true, 'Could not import users');
        $APP    = App::find($request->input('app_id'));
        if ($request->hasFile('sheet_file')
            and $APP
        ) {
            $columns    = [
                'email'    => $request->input('email'),
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'phone'    => $request->input('phone') ?: 'phone',
            ];
            $pathToFile = $model->saveFile($request->file('sheet_file'));
            $parser     = new ExcelParser($model, $APP);
            $parser->run($pathToFile, $columns);
            $totalSaved = $parser->getTotalSaved();
            $errors     = $parser->getErrors();
            if ($errors) {
                $errors = implode('<br/>', $errors);
                $result = $this->getResult(true, $errors);
            } else $result = $this->getResult(false, 'Users have been imported<br/>Total saved: ' . $totalSaved);
        }

        return $result;
    }

    public function getDailyUsage($id)
    {
        $APP   = $this->app;
        $model = AppUser::find($id);
        if (!$model)
            return redirect()->back();
        $title    = $model->name . ': Daily usage';
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

        $model       = AppUser::find($id);
        $clientAlias = $model->getUserAlias();
        $dailyUsage  = new Collection();
        $resource    = $this->getResourceByAliasFromBillingDB($clientAlias);
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
        $APP      = $this->app;
        $title    = $APP->name . ': SIP Accounts';
        $appUsers = $APP->users()->lists('email', 'id');
        $subtitle = 'Manage SIP Accounts';

        return view('appUsers.sip_accounts', compact('APP', 'title', 'subtitle', 'appUsers'));
    }

    public function getSipAccountsData(Request $request)
    {
        $fields = [
            'resource_ip_id',
            'username',
            'password',
            'reg_status'
        ];

        $appUser     = AppUser::find($request->app_user_id);
        $resource    = $this->getResourceByAliasFromBillingDB($appUser->getUserAlias());
        $sipAccounts = [];
        if ($resource) {
            $sipAccounts = $this->getFluentBilling('resource_ip')
                ->select($fields)
                ->whereResourceId($resource->resource_id)->get();
        }

        $sipAccounts = new Collection($sipAccounts);

        $datatables = Datatables::of($sipAccounts);
        $this->addSipActionButtons($datatables, $appUser);

        return $datatables->make(true);
    }

    public function addSipActionButtons($datatables, $appUser)
    {
        $datatables->add_column('actions', function ($sip) use ($appUser) {
            $options = [
                'url'   => 'app-users/edit-sip-account/' . $sip->resource_ip_id . '?app=' . $this->app->id,
                'name'  => '',
                'title' => 'Edit',
                'icon'  => 'fa fa-pencil',
                'class' => 'btn-success',
                'modal' => true
            ];
            $html    = $appUser->generateButton($options);

            $options = [
                'url'   => 'app-users/delete-sip-account/' . $sip->resource_ip_id . '?app=' . $this->app->id,
                'name'  => '',
                'title' => 'Delete',
                'icon'  => 'fa fa-remove',
                'class' => 'btn-danger',
                'modal' => true
            ];

            $html .= $appUser->generateButton($options);

            return $html;

        });
    }

    public function getCreateSipAccount()
    {
        $APP      = $this->app;
        $title    = 'Create new SIP account';
        $appUsers = $APP->users()->lists('email', 'id');

        return view('appUsers.create_edit_sip_account', compact('title', 'APP', 'appUsers'));
    }

    public function postCreateSipAccount(Request $request)
    {
        $this->validate($request, [
            'app_user_id' => 'required',
            'password'    => 'required'
        ]);
        $result  = $this->getResult(true, 'Could not create SIP account');
        $appUser = AppUser::find($request->app_user_id);
        if ($appUser->createSipAccount($request->password))
            $result = $this->getResult(false, 'Sip account has been created');

        return $result;
    }

    public function getEditSipAccount($id)
    {
        $APP       = $this->app;
        $title     = 'Edit SIP account';
        $model     = $this->getFluentBilling('resource_ip')
            ->whereResourceIpId($id)->first();
        $model->id = $model->resource_ip_id;
        $appUsers  = $APP->users()->lists('email', 'id');

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
        $APP       = $this->app;
        $title     = 'Delete SIP account';
        $model     = $this->getFluentBilling('resource_ip')
            ->whereResourceIpId($id)->first();
        $model->id = $model->resource_ip_id;
        $url       = Url::to('app-users/delete-sip-account/' . $id);

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

}
