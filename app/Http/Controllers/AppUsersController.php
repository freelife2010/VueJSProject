<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use yajra\Datatables\Datatables;

class AppUsersController extends AppBaseController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $app      = $this->app;
        $title    = $app->name . ': Users';
        $subtitle = 'Manage users';

        return view('appUsers.index', compact('app', 'title', 'subtitle'));
    }

    public function getCreate()
    {
        $title = 'Create new user';

        return view('appUsers.create_edit', compact('title'));
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
        return view('app.create_edit', compact('title', 'model'));
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
        $users = AppUser::select([
            'id',
            'name',
            'email',
            'phone',
            'last_status'])->whereAppId($this->app->id);

        return Datatables::of($users)
            ->add_column('actions', function($app) {
                return $app->getDefaultActionButtons('app');
            })
            ->make(true);
    }

}
