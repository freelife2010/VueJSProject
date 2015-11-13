<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use URL;
use yajra\Datatables\Datatables;

class AppCDRController extends AppBaseController
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
        $title    = $APP->name . ': View CDR';
        $subtitle = '';
        $callTypes = ['Outgoing calls', 'Incoming calls'];

        return view('appCDR.index', compact('APP', 'title', 'subtitle', 'callTypes'));
    }

    public function getData(Request $request)
    {
        $callType = $request->input('call_type');
        $fields = [
            'session_id',
            'start_time_of_date',
            'release_tod',
            'ani_code_id',
            'dnis_code_id',
            'call_duration',
            'agent_rate',
            'agent_cost',
            'origination_source_number',
            'origination_destination_number',
            'resource.alias'
        ];

        $resource = $this->getResourceByAliasFromBillingDB($this->app->alias);
        $cdr      = new Collection();
        if ($resource)
            $cdr = $this->getFluentBilling('client_cdr')->select($fields)
                    ->whereEgressClientId($resource->resource_id)
                    ->whereCallType($callType)
                    ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');

        return Datatables::of($cdr)->make(true);
    }

}
