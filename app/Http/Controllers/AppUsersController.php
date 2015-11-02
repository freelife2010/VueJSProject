<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppUserRequest;
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
        $result = $this->getResult(true, 'Could not create user');

        if (AppUser::create($request->input()))
            $result = $this->getResult(false, 'User created successfully');

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
