<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Helpers\ExcelParser;
use App\Http\Requests;
use App\Http\Requests\AppUserRequest;
use App\Http\Requests\DeleteRequest;
use App\Http\Requests\UploadUsersRequest;
use App\Jobs\DeleteAPPUserFromChatServer;
use App\Jobs\DeleteAPPUserToChatServer;
use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\App;
use App\Models\AppUser;
use App\Models\DID;
use Former\Facades\Former;
use Illuminate\Support\Collection;
use URL;
use yajra\Datatables\Datatables;

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
        $APP   = $this->app;
        $title = 'Create new user';

        return view('appUsers.create_edit', compact('title', 'APP'));
    }

    public function postCreate(AppUserRequest $request)
    {
        $result             = $this->getResult(true, 'Could not create user');
        $params             = $request->input();

        if ($user = AppUser::createUser($params)) {
            $result = $this->getResult(false, 'User created successfully');
            $this->dispatch(new StoreAPPUserToBillingDB($user, $user->app));
            $this->dispatch(new StoreAPPUserToChatServer($user));
        }

        return $result;
    }

    public function getEdit($id)
    {
        $title = 'Edit User';
        $model = AppUser::find($id);
        $APP   = $this->app;
        unset($model->password);
        return view('appUsers.create_edit', compact('title', 'model', 'APP'));
    }

    public function postEdit(AppUserRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not edit user');
        $model  = AppUser::find($id);
        if ($model->fill($request->input())
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
        $url   = Url::to('app-users/delete/'.$model->id);
        return view('appUsers.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete user');
        $model  = AppUser::find($id);
        if ($model->delete()) {
            $this->dispatch(new DeleteAPPUserFromChatServer($model));
            $result = $this->getResult(false, 'User deleted');
        }

        return $result;
    }

    public function getData()
    {
        $users = AppUser::select([
            'id',
            'name',
            'email',
            'phone',
            'last_status'])->whereAppId($this->app->id);

        return Datatables::of($users)
            ->edit_column('last_status', function($user) {
                return $user->last_status ? 'Active' : 'Inactive';
            })
            ->add_column('actions', function($user) {
                $options = [
                    'url'   => 'app-users/daily-usage/'.$user->id.'?app=' . $this->app->id,
                    'name'  => '',
                    'title' => 'View daily usage',
                    'icon'  => 'icon-calculator',
                    'class' => 'btn-default'
                ];
                $html = $user->generateButton($options);
                $html .= $user->getActionButtonsWithAPP('app-users', $this->app);

                return $html;
            })
            ->add_column('did', function($user) {
                $dids = $user->dids;
                $html = '';
                foreach ($dids as $did) {
                    $html .= $did->did.'<br/>';
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
        and $APP) {
            $columns    = [
                'email'    => $request->input('email'),
                'username' => $request->input('username'),
                'password' => $request->input('password')
            ];
            $pathToFile = $model->saveFile($request->file('sheet_file'));
            $parser     = new ExcelParser($model, $APP);
            $parser->run($pathToFile, $columns);
            $totalSaved = $parser->getTotalSaved();
            $errors = $parser->getErrors();
            if ($errors) {
                $errors = implode('<br/>', $errors);
                $result = $this->getResult(true, $errors);
            }
            else $result = $this->getResult(false, 'Users have been imported<br/>Total saved: '. $totalSaved);
        }

        return $result;
    }

    public function getDailyUsage($id)
    {
        $APP      = $this->app;
        $model    = AppUser::find($id);
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
        $clientId    = $this->getAPPUserIdFromBillingDB($model);
        $clientAlias = $model->getUserAlias($clientId, $this->app);
        $dailyUsage  = new Collection();
        $resource    = $this->getResourceByAliasFromBillingDB($clientAlias);
        if ($resource)
            $dailyUsage = $this->getFluentBilling('cdr_report')->selectRaw($fields)
                ->whereIngressClientId($resource->resource_id)->groupBy('report_time', 'duration');

        return Datatables::of($dailyUsage)->make(true);
    }

    public function getCallerIdInputs()
    {
        $dids = DID::all()->lists('did', 'did');

        $html = Former::select('caller_id')->addOption('Outside number', 0)
            ->options($dids)->placeholder('Select DID')->label('Number');

        return $html;
    }

}
