<?php

namespace App\Http\Controllers;

use App\Models\DID;
use Illuminate\Http\Request;

use App\Http\Requests;

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

    public function getCreate()
    {
        $title  = 'Buy DID';
        $did    = new DID();
        $states = $did->getStates();


        return view('did.create', compact('title', 'did', 'states'));
    }

    public function postCreate(Request $request)
    {
        $this->validate($request, [
            'expire_days' => 'required|numeric'
        ]);
        $result     = $this->getResult(true, 'Could not generate APP key');
        $appKey     = new AppKey();
        $app        = App::find($request->input('app_id'));
        $expireDays = $request->input('expire_days');
        if ($appKey->generateKeys($app, $expireDays))
            $result = $this->getResult(false, 'App key has been generated');

        return $result;
    }

    public function getCities(Request $request)
    {
        $state       = $request->state;
        $did         = new DID();
        $rateCenters = $did->getNPA($state);

        return $rateCenters;
    }

}
