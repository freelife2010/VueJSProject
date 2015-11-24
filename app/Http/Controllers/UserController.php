<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppUserRequest;
use App\Http\Requests\DeleteRequest;
use App\User;

use App\Http\Requests;
use Bican\Roles\Models\Role;
use URL;
use yajra\Datatables\Datatables;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title     = 'Developers list';
        $subtitle  = 'See developers';

        return view('users.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $users = User::all();

        foreach ($users as $i => $user) {
            if ($user->isAdmin())
                unset($users[$i]);
        }

        return Datatables::of($users)
            ->add_column('app_count', function ($user) {
                return $user->apps->count();
            })
            ->add_column('actions', function($user) {
                return $user->getDefaultActionButtons('users');
            })
            ->make(true);
    }

    public function getCreate()
    {
        $title = 'Create new developer';

        return view('users.create_edit', compact('title'));
    }

    public function postCreate(AppUserRequest $request)
    {
        $result                    = $this->getResult(true, 'Could not create developer');
        $params                    = $request->input();
        $params['password']        = bcrypt($params['password']);
        $params['active']          = true;
        $params['resent']          = 0;
        if ($user = User::create($params)) {
            $developerRole = Role::whereSlug('developer')->first();
            $user->attachRole($developerRole);
            $result = $this->getResult(false, 'Developer created successfully');
        }

        return $result;
    }

    public function getEdit($id)
    {
        $title = 'Edit developer';
        $model = User::find($id);
        unset($model->password);

        return view('users.create_edit', compact('title', 'model'));
    }

    public function postEdit(AppUserRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not edit developer');
        $model  = User::find($id);
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'Developer saved successfully');

        return $result;
    }

    public function getDelete($id)
    {
        $title = 'Delete developer ?';
        $model = User::find($id);
        $url   = Url::to('users/delete/'.$model->id);
        return view('users.delete', compact('title', 'model', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete developer');
        $model  = User::find($id);
        if ($model->delete()) {
            $result = $this->getResult(false, 'Developer deleted');
        }

        return $result;
    }
}
