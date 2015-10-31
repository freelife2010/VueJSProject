<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppRequest;
use App\Models\App;
use App\Http\Requests;
use yajra\Datatables\Facades\Datatables;

class AppController extends AppBaseController
{
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

    public function getDashboard()
    {
        $app      = $this->app;
        $title    = 'APP Dashboard: ' . $app->name;
        $subtitle = 'Manage APP';

        return view('app.dashboard', compact('title', 'subtitle', 'app'));
    }

    public function getCreate()
    {
        $title = 'Create new APP';

        return view('app/create_edit', compact('title'));
    }

    public function postCreate(AppRequest $request)
    {
        $result = $this->getResult(true, 'Could not create APP');

        $app = new App();
        if ($app->createApp($request->input()))
            $result = $this->getResult(false, 'App created successfully');

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
}
