<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use App\Models\App;
use App\Models\AppUser;
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
        $APP       = $this->app;
        $title     = $APP->name . ': View CDR';
        $subtitle  = '';
        $callTypes = ['Outgoing calls', 'Incoming calls'];
        $filterTypes = ['Peer to Peer', 'DID Calls', 'Toll Free Calls', 'Forwarded Calls', 'Dialed Calls', 'Mass Call'];

        return view('appCDR.index', compact('APP', 'title', 'subtitle', 'callTypes', 'filterTypes'));
    }

    public function getData(Request $request)
    {
        $callType = $request->input('call_type');
        $filter = $request->input('filter');
        $cdr      = $this->app->getCDR($filter)->whereCallType($callType);

        return Datatables::of($cdr)
            ->edit_column('trunk_id_origination', function($user) use ($callType) {
                $trunk = $user->trunk_id_origination;

                $trunk = explode('_', $trunk)[0];
                if ($trunk) {
                    try {
                        return App::where('tech_prefix', '=', $trunk)
                            ->first()
                            ->alias;
                    } catch (\Exception $e) {
                        return '';
                        die(var_dump($e->getMessage()));
                    }
                }
            })
            ->edit_column('alias', function($user) use ($callType) {
                $trunk = $user->trunk_id_termination;

                if ($trunk) {
                    $trunk = explode('_', $trunk)[0];
                    $parts = explode('-', $trunk);
                    try {
                        return AppUser::select(['users.name as app_user_name'])
                            ->join('app', 'app.id', '=', 'users.app_id')
                            ->where('app.tech_prefix', '=', $parts[0])
                            ->where('users.tech_prefix', '=', $parts[2])
                            ->first()
                            ->app_user_name;
                    } catch (\Exception $e) {
                        return '';
                        die(var_dump($e->getMessage()));
                    }
                }
            })->make(true);
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
        $resource = $this->getResourceByAliasFromBillingDB($this->app->getAppAlias());
        $cdr      = new Collection();
        if ($resource)
            $cdr = $this->getFluentBilling('client_cdr')->select($fields)
                ->whereEgressClientId($resource->resource_id)
                ->whereBetween('time', [$fromDate, $toDate])->get();


        $cdr = $this->formatCDRData($cdr);

        return $cdr;
    }

    public function getOverallCdrData(Request $request)
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
        $resource = $this->getResourceByAliasFromBillingDB($this->app->getAppAlias());
        $cdr      = new Collection();
        $apps     = \Auth::user()->apps;
        if ($resource)
            foreach ($apps as $app) {

            }
        $cdr = $this->getFluentBilling('client_cdr')->select($fields)
            ->whereEgressClientId($resource->resource_id)
            ->whereBetween('time', [$fromDate, $toDate])->get();


        $cdr = $this->formatCDRData($cdr);

        return $cdr;
    }

    public function getChartDailyUsageData(Request $request)
    {
        $fields =
            'report_time,
             duration,
             sum(ingress_bill_time)/60 as min,
             sum(ingress_call_cost+lnp_cost) as cost';

        $fromDate = date('Y-m-d H:i:s', strtotime($request->from_date));
        $toDate   = date('Y-m-d H:i:s', strtotime($request->to_date));

        $resource   = $this->getResourceByAliasFromBillingDB($this->app->getAppAlias());
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
