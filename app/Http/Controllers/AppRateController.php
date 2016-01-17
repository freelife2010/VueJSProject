<?php

namespace App\Http\Controllers;

use App\Helpers\BillingTrait;

use App\Http\Requests;
use App\Models\App;
use App\Models\AppRate;
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
        $appRate      = new AppRate($this->app);
        $rates = new Collection($appRate->getGlobalRates());

        return Datatables::of($rates)
            ->edit_column('rate', function ($rate) {
                return round($rate->rate, 2);
            })
            ->add_column('custom_rate', function ($rate) use ($appRate) {
                $params = [
                    'url'   => 'app-rates/add-rate/' . $rate->rate_id . '?app=' . $this->app->id,
                    'name'  => '',
                    'icon'  => 'fa fa-plus',
                    'class' => 'btn-primary',
                    'title' => 'Define app rate',
                    'modal' => true
                ];

                $customRate = $appRate->findAppRateByCode($rate->code);
                if ($customRate) {
                    $params['url']   = 'app-rates/edit-rate/' . $customRate->rate_id .
                        '?app=' . $this->app->id;
                    $params['icon']  = '';
                    $params['class'] = 'btn-default';
                    $params['name']  = round($customRate->rate, 2);
                    $params['title'] = 'Edit app rate';
                }

                return $this->app->generateButton($params);
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

        if ($appRate->createRate($rateId, $request->rate))
            $result = $this->getResult(false, 'Rate created');


        return $result;
    }

}
