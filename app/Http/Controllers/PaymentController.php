<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use yajra\Datatables\Datatables;

class PaymentController extends Controller
{
    use BillingTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title    = 'Payment history';
        $subtitle = 'View payment history';

        return view('payments.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $fields   = [
            'client_id',
            'invoice_id',
            'country',
            'city',
            'address1',
            'chargetotal',
            'confirmed',
            'created_time'
        ];
        $payments = $this->getFluentBilling('payline_history')->select($fields);

        return Datatables::of($payments)->make(true);
    }

}
