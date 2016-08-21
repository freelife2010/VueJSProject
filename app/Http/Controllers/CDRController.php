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
        $filterTypes = ['Peer to Peer', 'DID Calls', 'Toll Free Calls', 'Forwarded Calls', 'Dialed Calls', 'Mass Call'];
        $user = \Auth::user();
        $apps = App::whereAccountId($user->id)->lists('name', 'id')->toArray();

        return view('cdr.index', compact('title', 'subtitle', 'callTypes', 'filterTypes', 'apps'));
    }

    public function getData(Request $request)
    {
        $startDate = '1971-01-01';
        if ($request->input('from_date')){
            $startDate = $request->input('from_date');
        }
        $filter = $request->input('filter');
        $appId = $request->input('app');
        $callType = $request->input('call_type');
        $cdr = $this->queryCdr($filter, $appId, $callType, $startDate);

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
            })
            ->make(true);
    }

    protected function queryCdr($filter = 0, $appId = 0, $callType='0', $startDate = '')
    {
        $startDate = $startDate ?: '1971-01-01';
        $user = \Auth::user();

        $dailyTableName = 'client_cdr' . date('Ymd');//, strtotime('-1 day'));
        $query = $this->getFluentBilling($dailyTableName)
            ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');

        switch ($filter) {
            case 0:
                $query->where('ingress_client_id', '=', 429)
                    ->where('origination_source_host_name', '!=', '108.165.2.110');
                break;
            case 1:
                $client = $this->getFluentBilling('client')->where('name', '=', $user->email)->first();
                $clientId = $client->client_id;
                $query->where('ingress_client_id', '=', $clientId);
                break;
            case 2:
                $clientId = getClientIdByAliasFromBillingDB('Opentact_TF_Term');
                $query->where('egress_client_id', '=', $clientId);
                break;
            case 3:
                $query->where('origination_source_host_name', '=', '108.165.2.110');
                if ($appId) {
                    $alias = App::find($appId)->getAppAlias();
                    $query->where('trunk_id_termination', '=', $alias);
                }

                break;
            case 4:
                $query->where('origination_source_host_name', '!=', '108.165.2.110');
                if ($appId) {
                    $alias = App::find($appId)->getAppAlias();
                    $query->where('trunk_id_termination', '=', $alias);
                }
                break;
            case 5:
                if ($appId) {
                    $alias = App::find($appId)->getAppAlias();
                    $query->where('trunk_id_termination', '=', $alias . '_CC_term');
                }
                break;
            default:
                break;
        }

        return $query;

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