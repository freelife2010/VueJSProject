<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
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
                                SELECT max(rate) as rate, code_name, country
                                FROM  rate WHERE rate_table_id = ?
                                AND ((now() BETWEEN effective_date AND end_date)
                                    OR end_date IS NULL)
                                GROUP BY code_name, country', [$rateTableId]);

        $destinations = new Collection($destinations);

        return Datatables::of($destinations)
            ->add_column('custom_rate', function($dest) {
                return '';
            })
            ->make(true);
    }

    public function getDestinations()
    {

    }

}
