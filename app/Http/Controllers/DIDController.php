<?php

namespace App\Http\Controllers;

use App\Models\DID;
use Former\Facades\Former;
use Illuminate\Http\Request;

use App\Http\Requests;
use yajra\Datatables\Datatables;

class DIDController extends Controller
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

        return view('did.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $DIDs = DID::all();

        return Datatables::of($DIDs)->make(true);
    }

    public function getCreate(Request $request)
    {
        $title  = 'Buy DID';
        $did    = new DID();
        $states = $did->getStates();
        $states = array_combine($states, $states);

        return view('did.create', compact('title', 'did', 'states'));
    }


    public function postCreate(Request $request)
    {
        $this->validate($request, [
            'did' => 'required'
        ]);
        $result = $this->getResult(true, 'Could not buy DID');
        $did    = new DID();
        $response = $did->reserveDID($request->did);
        if (isset($response->reserveId)) {
            $params = $request->all();
            $params['reserve_id'] = $request->did;
            $storedDIDs = $request->session()->get('dids');
            $storedDID = $did->findReservedDID($request->did, $storedDIDs);
            if ($storedDID) {
                $params['did_type'] = $storedDID->category;
                $params['rate_center'] = $storedDID->RateCenter;
            }
            $did->fill($params);
            if ($did->save())
                $result = $this->getResult(false, 'DID has been acquired');
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

}
