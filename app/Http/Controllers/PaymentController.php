<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use App\Models\Payment;
use Auth;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

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
        $payments = Payment::whereAccountId(Auth::user()->id);

        return Datatables::of($payments)
            ->edit_column('amount', function($payment) {
                return round($payment->amount/100);
            })
            ->make(true);
    }

    public function getAddCredit()
    {
        return view('payments.add_credit_stripe');
    }

    public function postCreateStripe(Request $request)
    {
        $this->validate($request, [
            'amount'      => 'required',
            'stripeToken' => 'required'
        ]);

        $result = $this->getResult(true, 'Could not add credit');
        $user     = Auth::user();

        $charged = $user->charge($request->amount*100, [
            'receipt_email' => $user->email,
            'source'        => $request->stripeToken,
            'currency'      => 'usd',
            'description'   => 'Opentact credit'
        ]);

        if ($charged) {
            $result = $this->getResult(false, 'Credit added');
            Payment::createStripePayment($charged);
            $user->addCredit($charged->amount);
        }

        return $result;
    }

}
