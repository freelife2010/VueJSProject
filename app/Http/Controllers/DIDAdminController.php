<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppUserRequest;
use App\Http\Requests\DeleteRequest;
use App\Http\Requests\DeveloperRequest;
use App\Jobs\DeleteDeveloperFromBillingDB;
use App\Jobs\StoreDeveloperToBillingDB;
use App\Models\App;
use App\User;

use App\Http\Requests;
use Bican\Roles\Models\Role;
use Illuminate\Support\Str;
use URL;
use Yajra\Datatables\Datatables;

class DIDAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title    = 'DID vendors list';
        $subtitle = 'See DID vendors';

        return view('didAdmin.index', compact('title', 'subtitle'));
    }
}
