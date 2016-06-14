<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use App\Models\AppUser;
use App\Models\Payment;
use Auth;
use Config;
use DB;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Redirect;
use Session;
use Yajra\Datatables\Datatables;
use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\PaymentExecution;

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

    public function getAdmin()
    {
        $title    = 'Payment history';
        $subtitle = 'View payment history';

        return view('payments.admin', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $payments = Payment::whereAccountId(Auth::user()->id);

        return Datatables::of($payments)
            ->edit_column('amount', function ($payment) {
                return round($payment->amount / 100);
            })
            ->make(true);
    }

    public function getAdminData()
    {
        $payments = Payment::all();

        return Datatables::of($payments)
            ->edit_column('account_id', function ($payment) {
                return $payment->developer ? $payment->developer->email : '';
            })
            ->edit_column('amount', function ($payment) {
                return round($payment->amount / 100);
            })
            ->make(true);
    }

    public function getAddCredit()
    {
        return view('payments.add_credit');
    }

    public function postCreateStripe(Request $request)
    {
        $this->validate($request, [
            'amount'      => 'required',
            'stripeToken' => 'required'
        ]);

        $result = $this->getResult(true, 'Could not add credit');
        $user   = Auth::user();

        $charged = $user->charge($request->amount * 100, [
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

    public function postCreatePaypal(Request $request)
    {
        $payment = new Payment();

        try {
            $paypalPayment = $payment->makePayPalPayment($request->amount);
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            if (\Config::get('app.debug')) {
                echo "Exception: " . $ex->getMessage() . PHP_EOL;
                $err_data = json_decode($ex->getData(), true);
                exit;
            } else {
                die('Some error occur, sorry for inconvenient');
            }
        }

        foreach ($paypalPayment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        // add payment ID to session
        Session::put('paypal_payment_id', $paypalPayment->getId());

        if (isset($redirect_url)) {
            // redirect to paypal
            return Redirect::away($redirect_url);
        }

        Flash::error('Unknown error occurred');

        return redirect('payments');
    }

    public function getPaypalStatus(Request $request)
    {
        $payment_id = Session::get('paypal_payment_id');

        // clear the session payment ID
        Session::forget('paypal_payment_id');

        if (empty($request->get('PayerID')) || empty($request->get('token'))) {
            Flash::error('Payment failed');

            return redirect('payments');
        }

        $payment = new Payment();
        $result  = $payment->executePayPalPayment($payment_id, $request->get('PayerID'));


        if ($result->getState() == 'approved') {
            $transactions = $result->getTransactions();
            $amount       = $transactions[0]->amount->total * 100;
            Payment::createPaypalPayment($result, $amount);
            Auth::user()->addCredit($amount);
            Flash::success('Payment success');

            return redirect('payments/');
        }

        Flash::error('Payment failed');

        return redirect('payments/');
    }

}
