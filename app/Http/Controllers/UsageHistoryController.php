<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Helpers\PlaySMSTrait;
use App\Http\Requests\AppRequest;
use App\Http\Requests\DeleteRequest;
use App\Jobs\StoreAPPToBillingDB;
use App\Jobs\StoreAPPToChatServer;
use App\Models\App;
use App\Http\Requests;
use App\Models\AppUser;
use Illuminate\Http\Request;
use URL;
use Yajra\Datatables\Datatables;

class UsageHistoryController extends AppBaseController
{
    use BillingTrait;

    /**
     * AppController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
//        parent::__construct($request);
//        $this->middleware('auth');
//        $this->middleware('csrf');
//        $this->middleware('role:developer', [
//            'except' => [
//                'getEdit',
//                'postEdit',
//                'getDelete',
//                'postDelete'
//            ]
//        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title    = 'APP List';
        $subtitle = 'Usage History';

        return view('usageHistory.index', compact('title', 'subtitle'));
    }
    
    public function getData()
    {
        $developerClientId = $this->getFluentBilling('client')
            ->where('name', '=', \Auth::user()->email)
            ->first()
            ->client_id;
    }
}
