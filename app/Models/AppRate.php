<?php

namespace App\Models;


use App\Helpers\BillingTrait;

class AppRate extends BaseModel
{
    use BillingTrait;

    private $opentactRateTableId = 10;

    protected $app;
    protected $appRateTableId;

    public $rate;

    /**
     * @param mixed $app
     */
    public function __construct($app)
    {
        parent::__construct();
        $this->app            = $app;
        $this->appRateTableId = $this->getRateTableIdByName($this->app->name);
    }

    public function setRateById($rateId)
    {
        $this->rate = $this->getFluentBilling('rate')->whereRateId($rateId)->first();

    }

    public function getGlobalRates()
    {
        return $this->selectFromBillingDB('
                                SELECT max(rate) as rate, code_name as destination,
                                       country, code, rate_id
                                FROM  rate WHERE rate_table_id = ?
                                AND ((now() BETWEEN effective_date AND end_date)
                                    OR end_date IS NULL)
                                GROUP BY code_name, country, rate_id', [$this->opentactRateTableId]);
    }

    public function createRate($originalRateId, $ratePrice)
    {
        $rateData = $this->selectFromBillingDB('
                                SELECT *
                                FROM  rate WHERE rate_id = ?', [$originalRateId]);
        $result   = false;

        if ($rateData) {
            $rate                  = (array)$rateData[0];
            $rate['rate']          = $ratePrice;
            $rate['rate_table_id'] = $this->appRateTableId;
            $rate['create_time']   = date('Y-m-d H:i:s');
            unset($rate['rate_id']);
            $db     = $this->getFluentBilling('rate');
            $result = $db->insert($rate);
        }

        return $result;
    }

    public function saveRate($rateId, $ratePrice)
    {
        $db = $this->getFluentBilling('rate');

        return $db->where('rate_id', $rateId)->update(['rate' => $ratePrice]);
    }

    public function findAppRateByCode($code)
    {
        $rateData = $this->selectFromBillingDB('
                                SELECT rate_id, rate
                                FROM  rate WHERE code = ? AND rate_table_id = ?', [$code, $this->appRateTableId]);
        $result   = [];

        if ($rateData)
            $result = $rateData[0];

        return $result;
    }
}
