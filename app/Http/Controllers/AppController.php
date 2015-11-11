<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Http\Requests\AppRequest;
use App\Http\Requests\DeleteRequest;
use App\Jobs\StoreAPPToBillingDB;
use App\Jobs\StoreAPPToChatServer;
use App\Models\App;
use App\Http\Requests;
use App\Models\AppKey;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use URL;
use yajra\Datatables\Datatables;

class AppController extends AppBaseController
{
    use BillingTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        return redirect('app/list');
    }

    public function getList()
    {
        $title    = 'APP List';
        $subtitle = 'Manage APP';

        return view('app.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $apps = App::getApps([
            'id',
            'name',
            'presence']);

        return Datatables::of($apps)
            ->add_column('users', function ($app) {
                $users = $app->users;
                return count($users->all());
            })
            ->add_column('actions', function($app) {
                return $app->getDefaultActionButtons('app');
            })
            ->add_column('daily_active', function($app) {
                return '';
            })
            ->add_column('weekly_active', function($app) {
                return '';
            })
            ->add_column('monthly_active', function($app) {
                return '';
            })
            ->edit_column('presence', function($app) {
                $icon = $app->presence ? 'fa fa-check' : 'fa fa-remove';
                return '<em class="'.$icon.'"></em>';
            })
            ->make(true);
    }

    public function getDashboard()
    {
        $APP      = $this->app;
        $title    = 'APP Dashboard: ' . $APP->name;
        $subtitle = 'Manage APP';

        return view('app.dashboard', compact('title', 'subtitle', 'APP'));
    }

    public function getCreate()
    {
        $title = 'Create new APP';

        return view('app/create_edit', compact('title'));
    }

    public function getCheckBilling()
    {
        $currencyId = $this->getCurrencyIdFromBillingDB();
        $clientId   = $this->getCurrentUserIdFromBillingDB();
        return ['currencyId' => $currencyId, 'currentClientId' => $clientId];
    }

    public function postCreate(AppRequest $request)
    {
        $result = $this->getResult(true, 'Could not create APP');

        $app = new App();
        if ($app->createApp($request->input())) {
            $result = $this->getResult(false, 'App created successfully');
            $this->dispatch(new StoreAPPToBillingDB($app));
            $this->dispatch(new StoreAPPToChatServer($app));
        }

        return $result;
    }

    public function getEdit($id)
    {
        $title = 'Edit APP';
        $model = App::find($id);
        return view('app/create_edit', compact('title', 'model'));
    }

    public function postEdit(AppRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not edit APP');
        $model  = App::find($id);
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'App saved successfully');

        return $result;
    }

    public function getDelete($id)
    {
        $title = 'Delete APP ?';
        $model = App::find($id);
        $url   = Url::to('app/delete/'.$model->id);
        return view('appUsers.delete', compact('title', 'model', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete APP');
        $model  = App::find($id);
        $users  = $model->users;
        if ($users->count())
            $result = $this->getResult(true, 'Could not delete APP: It has users');
        elseif ($model->delete())
            $result = $this->getResult(false, 'APP deleted');

        return $result;
    }

    public function getDailyUsage()
    {
        $APP      = $this->app;
        $title    = $APP->name.': Daily usage';
        $subtitle = 'View daily usage';

        return view('app.daily_usage', compact('title', 'subtitle', 'APP'));
    }

    public function getDailyUsageData(Request $request)
    {
        $callType = $request->input('call_type');
        $fields =
            'report_time,
             duration,
             sum(ingress_bill_time)/60 as min,
             sum(ingress_call_cost+lnp_cost) as cost';

        $resource  = $this->getResourceByAliasFromBillingDB($this->app->alias);
        $dailyUsage = new Collection();
        if ($resource)
            $dailyUsage = $this->getFluentBilling('cdr_report')->selectRaw($fields)
                ->whereEgressClientId($resource->resource_id)->whereCallType($callType);

        return Datatables::of($dailyUsage)->make(true);
    }

}
