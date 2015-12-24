<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Http\Requests;
use DB;
use Illuminate\Http\Request;
use yajra\Datatables\Datatables;

class CDRController extends Controller
{
    use BillingTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title = 'CDR';
        $subtitle = '';

        return view('cdr.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $fields = [
            'session_id',
            'resource.alias',
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

        $cdr = $this->getFluentBilling('client_cdr')
                    ->select($fields)
                    ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');

        return Datatables::of($cdr)->make(true);
    }

    public function getChartData(Request $request)
    {
        $fields = [
            'session_id',
            'time',
            'start_time_of_date',
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

        $cdr = $this->getFluentBilling('client_cdr')
            ->select($fields)->whereBetween('time', [$fromDate, $toDate])->get();

        $cdr = $this->formatCDRData($cdr);

        return $cdr;
    }


}
