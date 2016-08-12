<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;
use App\Http\Requests;
use DB;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\App;
use App\Models\AppUser;

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
        $startDate = '1971-01-01';
        if ($request->input('from_date')){
            $startDate = $request->input('from_date');
        }
        $callType = $request->input('call_type');
        $cdr      = $this->queryCdr($callType,$startDate);

        return Datatables::of($cdr)
            ->edit_column('trunk_id_origination', function($user) use ($callType) {

                $trunkId = $callType == 0 ? $user->trunk_id_origination : $user->trunk_id_termination;
                if (empty($trunkId)) return '';
                return $trunkId;
                list($appTechPrefix, $userTechPrefix) = explode('-000-', str_ireplace(['_for', '_pbx'], '', $trunkId));
                return AppUser::select(['app.name as app_name'])
                    ->join('app', 'app.id', '=', 'users.app_id')
                    ->where('users.tech_prefix', '=', $userTechPrefix)
                    ->where('app.tech_prefix', '=', $appTechPrefix)
                    ->first()
                    ->app_name;
            })
            ->edit_column('alias', function($user) use ($callType) {
                $trunkId = $callType == 0 ? $user->trunk_id_origination : $user->trunk_id_termination;
                if (!$trunkId) return '';
//                if (empty($trunkId) || is_null($trunkId)) return '';
                return $trunkId;
                try {
                    list($appTechPrefix, $userTechPrefix) = explode('-000-', str_ireplace(['_for', '_pbx'], '', $trunkId));
                    return AppUser::select(['users.name as app_user_name'])
                        ->join('app', 'app.id', '=', 'users.app_id')
                        ->where('users.tech_prefix', '=', $userTechPrefix)
                        ->where('app.tech_prefix', '=', $appTechPrefix)
                        ->first()
                        ->app_user_name;
                } catch (\Exception $e) {
                    die(var_dump($trunkId));
                }
            })
            ->make(true);
    }

    protected function queryCdr($callType='0',$startDate = '')
    {
        $startDate = $startDate ?: '1971-01-01';
        $user = \Auth::user();

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
            'origination_destination_number'
        ];

        $clientId = $this->getFluentBilling('client')
            ->where('name', '=', $user->email)
            ->first()
            ->client_id;



        $colName = $callType == 1 ? 'ingress_client_id' : 'egress_client_id';
//        select origination_source_number, origination_destination_number, termination_source_number,
//        termination_destination_number,  time ,call_duration, trunk_id_origination, trunk_id_termination
//        from client_cdr20160803
        $dailyTableName = 'client_cdr20160805';//'client_cdr' . date('Ymd', strtotime('-1 day'));
        return $this->getFluentBilling($dailyTableName)
//            ->select($fields)
            ->where($colName, '=', $clientId)
            ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');

//        return $this->getFluentBilling('client_cdr')
//            ->select($fields)
//            ->whereCallType($callType)
//            ->where('time', '>', $startDate)
//            ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');
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
