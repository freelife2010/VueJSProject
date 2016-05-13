<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use App\Models\App;
use App\Models\AppRate;
use Former\Facades\Former;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Yajra\Datatables\Datatables;

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
        $appRate      = new AppRate($this->app);
        $rates = new Collection($appRate->getGlobalRates());

        return Datatables::of($rates)
            ->edit_column('rate', function ($rate) {
                return round($rate->rate, 2);
            })
            ->add_column('custom_rate', function ($rate) use ($appRate) {
                $input = Former::text('app_rate')->label('')
                    ->type('number')->step('0.01')
                    ->style('width: 80px');
                $params = [
                    'url'   => 'app-rates/add-rate/' . $rate->rate_id . '?app=' . $this->app->id,
                    'name'  => '',
                    'icon'  => 'fa fa-plus',
                    'class' => 'btn-primary add_rate_btn',
                    'title' => 'Set app rate'
                ];

                if ($rate->app_rate) {
                    $params['url']   = 'app-rates/edit-rate/' . $rate->app_rate_id .
                        '?app=' . $this->app->id;
                    $params['icon']  = 'fa fa-check';
                    $params['class'] = 'btn-success add_rate_btn';
                    $params['title'] = 'Change app rate';
                    $input->value = round($rate->app_rate, 2);
                }

                $html = "<div class=\"form-group\">$input</div>";
                $html .= $this->app->generateButton($params);
                return $html;
            })
            ->make(true);
    }

    public function getAddRate($rateId)
    {
        $title = 'Add rate';
        $APP   = $this->app;

        return view('appRates.add_edit_rate', compact('title', 'rateId', 'APP'));
    }

    public function getEditRate($rateId)
    {
        $title = 'Edit rate';
        $model = new AppRate($this->app);
        $model->setRateById($rateId);
        $APP   = $this->app;

        return view('appRates.add_edit_rate', compact('title', 'model', 'rateId', 'APP'));
    }

    public function postEditRate($rateId, Request $request)
    {
        $this->validate($request, [
            'rate' => 'required|numeric'
        ]);
        $result = $this->getResult(true, 'Could not edit rate');

        $appRate = new AppRate(App::find($request->app));

        if ($appRate->saveRate($rateId, $request->rate))
            $result = $this->getResult(false, 'Rate saved');


        return $result;
    }

    public function postAddRate($rateId, Request $request)
    {
        $this->validate($request, [
            'rate' => 'required|numeric'
        ]);
        $result = $this->getResult(true, 'Could not save rate');

        $appRate = new AppRate(App::find($request->app));
        if (!$appRate->getAppRateTableId())
            $result = $this->getResult(true, 'Could not find APP\'s rate table');

        elseif ($appRate->createRate($rateId, $request->rate))
            $result = $this->getResult(false, 'Rate created');


        return $result;
    }

    public function getCsv()
    {
        $appRate = new AppRate($this->app);
        $rates   = new Collection($appRate->getGlobalRates(false));
        $csv     = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(array_keys((array)$rates[0]));
        foreach ($rates as $rate) {
            $csv->insertOne((array)$rate);
        }

        $csv->output($this->app->name . ' - sellRates.csv');
    }

}
