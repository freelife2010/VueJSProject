<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use yajra\Datatables\Facades\Datatables;

class AppController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title = 'APP List';
        $subtitle = 'Manage APP';

        return view('app.index', compact('title', 'subtitle'));
    }

    public function getCreate()
    {
        $title = 'Create new APP';

        return view('app/create_edit', compact('title'));
    }

    public function getData()
    {
        $apps = App::select([
            'id',
            'name',
            'account_id']);

        return Datatables::of($apps)
            ->add_column('users', function ($app) {
                $users = $app->users;
                return count($users->all());
            })
            ->add_column('actions', function($group) {
                return $group->getDefaultActionButtons('groups');
            })
            ->make();
    }
}
