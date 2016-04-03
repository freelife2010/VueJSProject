<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use URL;
use Yajra\Datatables\Datatables;

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

    public function getChartData(Request $request)
    {
        $fields = [
            'session_id',
            'time',
            'release_tod',
            'ani_code_id',
            'dnis_code_id',
            'call_duration',
            'agent_rate',
            'agent_cost',
            'origination_source_number',
            'origination_destination_number'
        ];

        $fromDate = date('Y-m-d H:i:s', strtotime($request->from_date));
        $toDate   = date('Y-m-d H:i:s', strtotime($request->to_date));
        $resource = $this->getResourceByAliasFromBillingDB($this->app->alias);
        $cdr      = new Collection();
        if ($resource)
            $cdr = $this->getFluentBilling('client_cdr')->select($fields)
                    ->whereEgressClientId($resource->resource_id)
                    ->whereBetween('time', [$fromDate, $toDate])->get();


        $cdr = $this->formatCDRData($cdr);

        return $cdr;
    }

    public function getChartDailyUsageData(Request $request)
    {
        $fields     =
            'report_time,
             duration,
             sum(ingress_bill_time)/60 as min,
             sum(ingress_call_cost+lnp_cost) as cost';

        $fromDate   = date('Y-m-d H:i:s', strtotime($request->from_date));
        $toDate     = date('Y-m-d H:i:s', strtotime($request->to_date));

        $resource   = $this->getResourceByAliasFromBillingDB($this->app->alias);
        $dailyUsage = new Collection();
        if ($resource)
            $dailyUsage = $this->getFluentBilling('cdr_report')->selectRaw($fields)
                ->whereEgressClientId($resource->resource_id)
                ->whereBetween('report_time', [$fromDate, $toDate])
                ->groupBy('report_time', 'duration')->get();

        $dailyUsage = $this->formatCDRData($dailyUsage, 'report_time');

        return $dailyUsage;
    }

}
