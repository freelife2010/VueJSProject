<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppUserRequest;
use App\Http\Requests\DeleteRequest;
use App\Http\Requests\DeveloperRequest;
use App\Jobs\DeleteDeveloperFromBillingDB;
use App\Models\App;
use App\User;

use App\Http\Requests;
use Bican\Roles\Models\Role;
use URL;
use Yajra\Datatables\Datatables;

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
                $totalApps = $user->apps->count();
                return sprintf('<a href="%s" class="btn btn-sm btn-default">%s</a>',
                    url('users/app-list/'.$user->id), $totalApps);
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

    public function postCreate(DeveloperRequest $request)
    {
        $result                    = $this->getResult(true, 'Could not create developer');
        $params                    = $request->input();
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

    public function postEdit(DeveloperRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not edit user');
        $model  = User::find($id);
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'User saved successfully');

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
        $email  = $model->email;
        $model->email = "{$model->email}.deleted";
        if ($model->save() and $model->delete()) {
            $this->dispatch(new DeleteDeveloperFromBillingDB($email));
            $result = $this->getResult(false, 'Developer deleted');
        }

        return $result;
    }

    public function getEditProfile($id)
    {
        $title = 'Edit own profile';
        $model = User::find($id);

        unset($model->password);

        return view('users.create_edit', compact('title', 'model'));
    }

    public function getAppList($id)
    {
        $model    = User::findOrFail($id);
        $title    = "$model->email: APP list";
        $subtitle = 'View developer apps';

        return view('users.app_list', compact('title', 'model', 'subtitle'));
    }

    public function getApps($userId)
    {
        $apps = App::select([
            'id',
            'tech_prefix',
            'name',
            'presence'])->whereAccountId($userId);

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
            ->setRowId('id')
            ->make(true);
    }
}
