<?php

namespace App\Models;

use Anouar\Paypalpayment\Facades\PaypalPayment;

class Paypal extends BaseModel
{
    private $_apiContext;
    private $_ClientId = 'AbHqvuoHsRWzMOVvaMSwL10wsdmTJGV3K7IVAG_PfP44U8hjHgonkhEhJyjOiSAvbwQOIX0CcSlLMkJq';
    private $_ClientSecret='EEZ2UD4USeakfVucaY-xhJKFoAvgpiTaz-prEo7w81oSZcgvXjtrhlj7jp_ub2Qg8JpJaD85tQDKp7jU';

    public function __construct()
    {
        parent::__construct();
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

    public function getBalance()
    {
        return Paypalpayment::amount();
    }

    public function getPayments($count, $startIndex)
    {
        return Paypalpayment::getAll(['count' => $count, 'start_index' => $startIndex], $this->_apiContext);

    }
}
