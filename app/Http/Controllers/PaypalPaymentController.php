<?php

namespace App\Http\Controllers;


use Anouar\Paypalpayment\Facades\PaypalPayment;
use App\Http\Requests;

class PaypalPaymentController extends Controller
{
    private $_apiContext;
    private $_ClientId = 'AVJx0RArQzkCCsWC0evZi1SsoO4gxjDkkULQBdmPNBZT4fc14AROUq-etMEU';
    private $_ClientSecret='EH5F0BAxqonVnP8M4a0c6ezUHq-UT-CWfGciPNQOdUlTpWPkNyuS6eDN-tpB';

    public function __construct()
    {

        $this->_apiContext = Paypalpayment::ApiContext($this->_ClientId, $this->_ClientSecret);

        $this->_apiContext->setConfig(array(
            'mode' => 'sandbox',
            'service.EndPoint' => 'https://api.sandbox.paypal.com',
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled' => true,
            'log.FileName' => __DIR__.'/../PayPal.log',
            'log.LogLevel' => 'FINE'
        ));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
    }

}
