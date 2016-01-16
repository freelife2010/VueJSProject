<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use yajra\Datatables\Datatables;

class AppRateController extends AppBaseController
{
    use BillingTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Sell rates';
        $subtitle = 'Manage sell rates';

        return view('appRates.index', compact('title', 'subtitle', 'APP'));
    }

    public function getData()
    {
        $rateTableId  = $this->getRateTableIdByName($this->app->name);
        $destinations = $this->selectFromBillingDB('
                                SELECT max(rate) as rate, code_name, country, rate_id
                                FROM  rate WHERE rate_table_id = ?
                                AND ((now() BETWEEN effective_date AND end_date)
                                    OR end_date IS NULL)
                                GROUP BY code_name, country, rate_id', [$rateTableId]);

        $destinations = new Collection($destinations);

        return Datatables::of($destinations)
            ->add_column('custom_rate', function ($dest) {
                $params = [
                    'url'   => 'app-rates/add-rate/' . $dest->rate_id . '?app=' . $this->app->id,
                    'name'  => '',
                    'icon'  => 'fa fa-plus',
                    'class' => 'btn-primary',
                    'title' => 'Define custom rate',
                    'modal' => true
                ];

                return $this->app->generateButton($params);
            })
            ->make(true);
    }

    public function getAddRate($rateId)
    {
        $title = 'Add rate';

        return view('appRates.add_rate', compact('title','rateId'));
    }

    public function postAddRate($rateId, Request $request)
    {
        $this->validate($request, [
            'rate' => 'required|numeric'
        ]);
        $result  = $this->getResult(true, 'Could not save rate');

        $rateData = $this->selectFromBillingDB('
                                SELECT *
                                FROM  rate WHERE rate_id = ?', [$rateId]);
        if ($rateData) {
            $rate               = (array)$rateData[0];
            $rate['rate']       = $request->rate;
            unset($rate['rate_id']);
            $db = $this->getFluentBilling('rate');
            if ($db->insert($rate))
                $result = $this->getResult(false, 'Rate created');
        }


        return $result;
    }

}
