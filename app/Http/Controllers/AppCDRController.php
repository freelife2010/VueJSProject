<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
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
        $subtitle    = '';

        return view('appCDR.index', compact('APP', 'title', 'subtitle'));
    }

    public function getData()
    {
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
        ];

        $resource = $this->getResourceByAliasFromBillingDB($this->app->alias);

        $cdr = $this->getFluentBilling('client_cdr')->select($fields)
                    ->whereEgressClientId($resource->resource_id)->whereCallType(1);

        return Datatables::of($cdr)->make(true);
    }

}
