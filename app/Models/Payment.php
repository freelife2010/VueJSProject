<?php

namespace App\Models;

use Auth;
use Config;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;

class Payment extends BaseModel
{
    private $_paypal_api_context;

    protected $fillable = ['account_id', 'amount', 'stripe_transaction_id'];

    public function developer()
    {
        return $this->belongsTo('App\User', 'account_id');
    }

    public static function createStripePayment($charge)
    {
        $payment                 = new Payment();
        $payment->account_id     = \Auth::user()->id;
        $payment->amount         = $charge->amount;
        $payment->transaction_id = $charge->balance_transaction;
        $payment->type           = 'stripe';
        $payment->save();
    }

    public static function createPaypalPayment($paypalResult, $amount)
    {
        $payment                 = new Payment();
        $payment->account_id     = \Auth::user()->id;
        $payment->amount         = $amount;
        $payment->transaction_id = $paypalResult->id;
        $payment->type           = 'paypal';
        $payment->save();

    }

    protected function preparePayPalAPI()
    {
        $paypal_conf = Config::get('paypal');
        $this->_paypal_api_context = new ApiContext(new OAuthTokenCredential($paypal_conf['client_id'], $paypal_conf['secret']));
        $this->_paypal_api_context->setConfig($paypal_conf['settings']);
    }

    public function makePayPalPayment($price)
    {
        $this->preparePayPalAPI();

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item_1 = new Item();
        $item_1->setName('Opentact credit')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($price);

        $item_list = new ItemList();
        $item_list->setItems([$item_1]);

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($price);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription('Opentact credit supplement by developer: '.Auth::user()->email);

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(url('payments/paypal-status'))
            ->setCancelUrl(url('payments/paypal-status'));

        $payment = new PaypalPayment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));

        return  $payment->create($this->_paypal_api_context);
    }

    public function executePayPalPayment($payment_id, $payerId)
    {
        $this->preparePayPalAPI();

        $payment = PaypalPayment::get($payment_id, $this->_paypal_api_context);

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        //Execute the payment
        return $payment->execute($execution, $this->_paypal_api_context);
    }
}
