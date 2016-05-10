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
    public function __construct($app = null)
    {
        parent::__construct();
        if ($app) {
            $this->app            = $app;
            $this->appRateTableId = $this->getRateTableIdByName("{$this->app->tech_prefix}_IDD");
            if (!$this->appRateTableId)
                $this->appRateTableId = $this->getRateTableIdByName($this->app->tech_prefix);
        }
    }

    public function setRateById($rateId)
    {
        $this->rate = $this->getFluentBilling('rate')->whereRateId($rateId)->first();

    }

    public function getGlobalRates($withAppRate = true)
    {
        $appRateFields = $withAppRate ?
            ', app_rate.rate as app_rate,
            app_rate.rate_id AS app_rate_id' : '';
        return $this->selectFromBillingDB('
                                SELECT MAX (rate.rate) AS rate,
                                    rate.code_name AS destination,
                                    rate.country,
                                    rate.code,
                                    rate.rate_id'.$appRateFields.'
                                FROM  rate
                                LEFT JOIN rate AS app_rate
                                  ON app_rate.rate_table_id = ?
                                  AND app_rate.code = rate.code
                                WHERE rate.rate_table_id = ?
                                AND ((now() BETWEEN rate.effective_date AND rate.end_date)
                                    OR rate.end_date IS NULL)
                                AND rate.country IS NOT NULL
                                GROUP BY
                                  rate.code_name,
                                  rate.country,
                                  rate.rate_id,
                                  app_rate.rate_id', [$this->appRateTableId, $this->opentactRateTableId]);
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

    /**
     * @return mixed
     */
    public function getAppRateTableId()
    {
        return $this->appRateTableId;
    }


}
