<?php

namespace App\Http\Controllers;

use App\Models\AppRate;
use Former\Facades\Former;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Yajra\Datatables\Datatables;

class RateController extends Controller
{
    public function getIndex()
    {
        $title    = 'Sell rates';
        $subtitle = 'Manage sell rates';

        return view('rates.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $appRate = new AppRate();
        $rates   = new Collection($appRate->getGlobalRates());

        return Datatables::of($rates)
            ->edit_column('rate', function ($rate) use ($appRate) {
                $input           = Former::text('rate')->label('')
                    ->type('number')->step('0.01')
                    ->style('width: 80px');
                $params['name']  = '';
                $params['url']   = 'rates/edit-rate/' . $rate->rate_id;
                $params['icon']  = 'fa fa-check';
                $params['class'] = 'btn-success add_rate_btn';
                $params['title'] = 'Change opentact rate';
                $input->value    = round($rate->rate, 2);

                $html = "<div class=\"form-group\">$input</div>";
                $html .= $appRate->generateButton($params);

                return $html;
            })
            ->make(true);
    }

    public function getCsv()
    {
        $appRate = new AppRate();
        $rates   = new Collection($appRate->getGlobalRates(false));
        $csv     = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(array_keys((array)$rates[0]));
        foreach ($rates as $rate) {
            $csv->insertOne((array)$rate);
        }

        $csv->output('opentactSellRates.csv');
    }

    public function postEditRate($rateId, Request $request)
    {
        $this->validate($request, [
            'rate' => 'required|numeric'
        ]);
        $result = $this->getResult(true, 'Could not edit rate');

        $appRate = new AppRate();

        if ($appRate->saveRate($rateId, $request->rate))
            $result = $this->getResult(false, 'Rate saved');


        return $result;
    }
}
