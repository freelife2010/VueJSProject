<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Http\Requests;
use DB;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

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
        $title     = 'CDR';
        $subtitle  = '';
        $callTypes = ['Outgoing calls', 'Incoming calls'];

        return view('cdr.index', compact('title', 'subtitle', 'callTypes'));
    }

    public function getData(Request $request)
    {
//        DB::enableQueryLog();
        $startDate = '1971-01-01';
        if ($request->input('from_date')){
            $startDate = $request->input('from_date');
        }
        $callType = $request->input('call_type');
        $cdr      = $this->queryCdr($callType,$startDate);
//        dd(DB::getQueryLog());
        return Datatables::of($cdr)->make(true);
    }

    protected function queryCdr($callType='0',$startDate)
    {
        $fields = [
            'time',
            'trunk_id_origination',
            'resource.alias',
            'ani_code_id',
            'dnis_code_id',
            'call_duration',
            'agent_rate',
            'agent_cost',
            'origination_source_number',
            'origination_destination_number',
        ];


        return $this->getFluentBilling('client_cdr')
            ->select($fields)
            ->whereCallType($callType)
            ->where('time', '>', $startDate)
            ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');
    }

    public function getChartData(Request $request)
    {
        $fields       = [
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
        $appEgressIds = \Auth::user()->getAllAppsEgressIds();
        $fromDate     = date('Y-m-d H:i:s', strtotime($request->from_date));
        $toDate       = date('Y-m-d H:i:s', strtotime($request->to_date));

        $cdr = $this->getFluentBilling('client_cdr')
            ->select($fields)
            ->whereIn('egress_client_id', $appEgressIds)
            ->whereBetween('time', [$fromDate, $toDate])->get();

        $cdr = $this->formatCDRData($cdr);

        return $cdr;
    }

    public function getCsv(Request $request)
    {
        $cdr = $this->queryCdr($request->call_type)->get();
        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(array_keys((array)$cdr[0]));
        foreach ($cdr as $entry) {
            $csv->insertOne((array)$entry);
        }

        $csv->output('opentactCDR.csv');
    }


}
