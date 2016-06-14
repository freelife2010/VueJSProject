<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Payment;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\Http\Requests;
use Laracasts\Flash\Flash;
use Redirect;
use Session;
use Yajra\Datatables\Facades\Datatables;

class AppUserPaymentController extends AppBaseController
{

    public function getIndex($id)
    {
        $APP      = $this->app;
        $user     = AppUser::findOrFail($id);
        $title    = "$user->email: payment history";
        $subtitle = "View user payment history";

        return view('user_payments.index', compact('APP', 'title', 'subtitle', 'user'));

    }

    public function getData($id)
    {
        $user     = AppUser::findOrFail($id);
        $payments = DB::table('user_payments')->whereUserId($user->id);

        return Datatables::of($payments)
            ->edit_column('amount', function ($payment) {
                return round($payment->amount / 100);
            })
            ->make(true);
    }

    public function getAddCredit($id)
    {
        $user = AppUser::findOrFail($id);

        return view('user_payments.add_credit', compact('user'));
    }

    public function postCreateStripe($id, Request $request)
    {
        $this->validate($request, [
            'amount'      => 'required',
            'stripeToken' => 'required'
        ]);

        $result = $this->getResult(true, 'Could not add credit');
        $user   = AppUser::findOrFail($id);

        $charged = $user->charge($request->amount * 100, [
            'receipt_email' => $user->email,
            'source'        => $request->stripeToken,
            'currency'      => 'usd',
            'description'   => 'Opentact credit'
        ]);

        if ($charged) {
            $result = $this->getResult(false, 'Credit added');
            $this->createStripePayment($charged, $user);
            $user->addCredit($charged->amount);
        }

        return $result;
    }

    public function postCreatePaypal($id, Request $request)
    {
        $user    = AppUser::findOrFail($id);
        $payment = new Payment();

        try {
            $paypalPayment = $payment->makePayPalPayment($request->amount, $user,
                route('user_payments.paypal_status', ['id' => $user->id, 'app' => $user->app->id]));
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

        return redirect()->route('user_payments.index', ['id' => $user->id, 'app' => $this->app->id]);
    }

    public function getPaypalStatus($id, Request $request)
    {
        $payment_id = Session::get('paypal_payment_id');
        $user       = AppUser::findOrFail($id);

        // clear the session payment ID
        Session::forget('paypal_payment_id');

        if (empty($request->get('PayerID')) || empty($request->get('token'))) {
            Flash::error('Payment failed');

            return redirect()->route('user_payments.index', ['id' => $user->id, 'app' => $user->app->id]);
        }

        $payment = new Payment();
        $result  = $payment->executePayPalPayment($payment_id, $request->get('PayerID'));


        if ($result->getState() == 'approved') {
            $transactions = $result->getTransactions();
            $amount       = $transactions[0]->amount->total * 100;
            $this->createPaypalPayment($result, $amount, $user);
            $user->addCredit($amount);
            Flash::success('Payment success');

            return redirect()->route('user_payments.index', ['id' => $user->id, 'app' => $user->app->id]);
        }

        Flash::error('Payment failed');

        return redirect()->route('user_payments.index', ['id' => $user->id, 'app' => $user->app->id]);
    }

    private function createStripePayment($charge, $user)
    {
        $params = [
            'user_id'        => $user->id,
            'amount'         => $charge->amount,
            'transaction_id' => $charge->balance_transaction,
            'type'           => 'stripe',
            'created_at'     => new Carbon(),
            'updated_at'     => new Carbon()
        ];
        DB::table('user_payments')->insert($params);
    }

    private function createPaypalPayment($result, $amount, $user)
    {
        $params = [
            'user_id'        => $user->id,
            'amount'         => $amount,
            'transaction_id' => $result->id,
            'type'           => 'paypal',
            'created_at'     => new Carbon(),
            'updated_at'     => new Carbon()
        ];
        DB::table('user_payments')->insert($params);
    }
}
