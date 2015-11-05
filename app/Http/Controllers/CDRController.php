<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use DB;
use yajra\Datatables\Datatables;

class CDRController extends Controller
{
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
            'origination_source_number',
            'origination_source_host_name',
            'origination_destination_number',
            'origination_destination_host_name',
            'origination_call_id',
            'start_time_of_date',
            'release_tod'
        ];

        $cdr = DB::connection('billing')->table('client_cdr')->select($fields);

        return Datatables::of($cdr)->make(true);
    }

}
