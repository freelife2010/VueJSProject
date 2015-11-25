<?php

namespace App\Http\Controllers;

use App\Models\DID;
use DB;
use Former\Facades\Former;
use Illuminate\Http\Request;

use App\Http\Requests;
use yajra\Datatables\Datatables;

class DIDController extends AppBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title = 'Manage DID';
        $subtitle = '';
        $APP = $this->app;

        return view('did.index', compact('title', 'subtitle', 'APP'));
    }

    public function getData()
    {
        $DIDs = DID::whereAppId($this->app->id);

        return Datatables::of($DIDs)->make(true);
    }

    public function getCreate(Request $request)
    {
        $title   = 'Buy DID';
        $did     = new DID();
        $APP     = $this->app;
        $states  = $did->getStates();
        $states  = array_combine($states, $states);
        $actions = DB::table('did_action')->lists('name', 'id');

        return view('did.create', compact('title', 'did', 'states', 'APP', 'actions'));
    }


    public function postCreate(Request $request)
    {
        $this->validate($request, [
            'did'        => 'required',
            'action'     => 'required'
        ]);
        $result = $this->getResult(true, 'Could not buy DID');
        $did    = new DID();
        $response = 1;
        if (1) {
            $did->fillParams($request, 1);
            if ($did->save()) {
                $result = $this->getResult(false, 'DID has been acquired');
                $did->createDIDParameters($request);
            }
        } elseif (isset($response->error))
            $result = $this->getResult(true, 'Error occurred. Error message: '. $response->error);

        return $result;
    }

    public function getCities(Request $request)
    {
        $state       = $request->state;
        $did         = new DID();
        $rateCenters = $did->getNPA($state);
        $rateCenters = $did->getList($rateCenters, 'RateCenter');

        return Former::select('rate_center')->options($rateCenters)->raw();

    }

    public function getNumbers(Request $request)
    {
        $state       = $request->state;
        $rateCenter  = (isset($request->rate_center)
                        and $request->rate_center != 'All') ? $request->rate_center : '';
        $did         = new DID();
        $numbers     = $did->getAvailableNumbers($state, $rateCenter);
        if (!empty($numbers->Numbers)) {
            $request->session()->put('dids', ($numbers->Numbers));
            $numbers = $did->getList($numbers->Numbers, 'TN', false);
        } else $numbers = ['Not found'];

        return Former::select('did')->options($numbers)->raw();
    }

    public function getParameters(Request $request)
    {
        $this->validate($request, [
            'did_action' => 'required'
        ]);
        $html = '';
        $parameters = DB::table('did_action_parameters')->select(['name', 'id'])
            ->whereActionId($request->did_action)->get();
        if ($parameters)
            $html = Former::label('Action parameter(s)');
        foreach ($parameters as $parameter) {
            $html .= DID::getActionParameterHtml($parameter, $this->app);
        }

        return $html;

    }

}
