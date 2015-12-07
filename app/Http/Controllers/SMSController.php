<?php

namespace App\Http\Controllers;

use App\Helpers\PlaySMSTrait;

use App\Http\Requests;
use Illuminate\Support\Collection;
use yajra\Datatables\Datatables;

class SMSController extends Controller
{
    use PlaySMSTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getHistory()
    {
        $title    = 'SMS history';
        $subtitle = 'View SMS history';

        return view('sms.history', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $smsHistory = $this->checkSMSInbox();
        $smsHistory = isset($smsHistory['data']) ? new Collection($smsHistory['data']) : new Collection();

        return Datatables::of($smsHistory)
            ->edit_column('dt', function($entry) {
                return date('d.m.Y H:i:s', strtotime($entry['dt']));
            })
            ->make(true);
    }
}
