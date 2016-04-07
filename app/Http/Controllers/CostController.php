<?php

namespace App\Http\Controllers;


use App\Http\Requests;
use App\Http\Requests\DeleteRequest;
use App\Models\Country;
use App\Models\DID;
use App\Models\DIDCost;
use App\Models\SMS;
use App\Models\SMSCost;
use Former;
use Illuminate\Http\Request;
use URL;
use Yajra\Datatables\Datatables;

class CostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDid()
    {
        $title                = 'DID cost';
        $subtitle             = 'Modify DID cost';
        $defaultCost          = DIDCost::whereState('default')->first();
        $defaultButtonOptions = [
            'type'  => 'btn-default',
            'label' => 'Set default cost'
        ];
        $defaultButtonOptions = !$defaultCost ?
            $defaultButtonOptions :
            ['type' => 'btn-green', 'label' => 'Edit default cost'];

        return view('costs.did',
            compact('title', 'subtitle', 'defaultCost', 'defaultButtonOptions'));
    }

    public function getDidData()
    {
        $didCosts = DIDCost::where('state', '!=', 'default')->where('rate_center', '!=', 'default')->get();


        return Datatables::of($didCosts)
            ->edit_column('country_id', function ($cost) {
                if ($cost->country) return $cost->country->name;
            })
            ->add_column('actions', function ($cost) {
                $urls['edit']   = url('costs/did-edit/' . $cost->id);
                $urls['delete'] = url('costs/did-delete/' . $cost->id);

                return $cost->getDefaultActionButtons('', $urls);
            })
            ->make(true);
    }

    public function getDidCreate()
    {
        $title     = 'Set new cost';
        $countries = Country::getCountryList();

        return view('costs.create_edit_did', compact('title', 'countries'));
    }

    public function getDidStates(Request $request)
    {
        $did    = new DID();
        $states = $did->getStates();
        $states = array_combine($states, $states);

        return Former::select('state')->options($states)->placeholder('Select state');

    }

    public function getDidCities(Request $request)
    {
        $state       = $request->state;
        $did         = new DID();
        $rateCenters = $did->getNPA($state);
        $rateCenters = $did->getList($rateCenters, 'RateCenter');

        return Former::select('rate_center')->options($rateCenters);

    }

    public function postDidCreate(Request $request)
    {
        $this->validate($request, [
            'country_id' => 'required',
            'value'      => 'required|numeric'
        ]);
        $result                = $this->getResult(true, 'Could not set new cost');
        $params                = $request->all();
        $params['rate_center'] = $params['rate_center'] != 0 ?: 'All';
        if ($didCost = DIDCost::create($params)) {
            $result = $this->getResult(false, 'New cost has been set');
        }

        return $result;
    }

    public function getDidEdit($id)
    {
        $title     = 'Edit cost';
        $model     = DIDCost::find($id);
        $countries = Country::getCountryList();
        $did       = new DID();
        $states    = $did->getStates();
        $states    = array_combine($states, $states);

        return view('costs.create_edit_did', compact('title', 'model', 'countries', 'states'));
    }

    public function postDidEdit(Request $request, $id)
    {
        $this->validate($request, [
            'country_id' => 'required',
            'value'      => 'required|numeric'
        ]);
        $result             = $this->getResult(true, 'Could not edit cost');
        $model              = DIDCost::find($id);
        $model->state       = $request->state ?: '';
        $model->rate_center = $request->rate_center ?: '';
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'Cost saved successfully');

        return $result;
    }

    public function getDidDelete($id)
    {
        $title = 'Delete cost ?';
        $model = DIDCost::find($id);
        $url   = Url::to('costs/did-delete/' . $model->id);

        return view('costs.delete', compact('title', 'model', 'url'));
    }

    public function postDidDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete cost');
        $model  = DIDCost::find($id);
        if ($model->delete()) {
            $result = $this->getResult(false, 'Cost deleted');
        }

        return $result;
    }

    public function getDidDefault()
    {
        $title       = 'Set default DID cost';
        $defaultCost = DIDCost::whereState('default')->first();

        return view('costs.did_default_cost', compact('title', 'defaultCost'));
    }

    public function postDidDefaultCreate(Request $request)
    {
        $this->validate($request, [
            'value' => 'required|numeric'
        ]);
        $result                = $this->getResult(true, 'Could not set default cost');
        $params                = $request->all();
        $params['state']       = 'default';
        $params['rate_center'] = 'default';
        $defaultCost           = DIDCost::whereState('default')->first();
        if ($defaultCost)
            $defaultCost->delete();

        if (DIDCost::create($params))
            $result = $this->getResult(false, 'New default cost has been set');

        return $result;
    }

    public function getSms()
    {
        $title    = 'SMS cost';
        $subtitle = 'Modify SMS cost';

        return view('costs.sms', compact('title', 'subtitle'));
    }

    public function getSmsData()
    {
        $smsCosts = SMSCost::all();


        return Datatables::of($smsCosts)
            ->add_column('name', function ($cost) {
                return $cost->country ? $cost->country->name : '';
            })
            ->add_column('code', function ($cost) {
                return $cost->country ? $cost->country->code : '';
            })
            ->add_column('actions', function ($cost) {
                $urls['edit']   = url('costs/sms-edit/' . $cost->id);
                $urls['delete'] = url('costs/sms-delete/' . $cost->id);

                return $cost->getDefaultActionButtons('', $urls);
            })
            ->make(true);
    }

    public function getSmsCreate()
    {
        $title     = 'Set new cost';
        $countries = Country::all()->lists('name', 'id');
        $countries = $countries->sort();

        return view('costs.create_edit_sms', compact('title', 'countries'));
    }


    public function postSmsCreate(Request $request)
    {
        $this->validate($request, [
            'countries'   => 'required',
            'cents_value' => 'required|numeric'
        ]);
        $result = $this->getResult(true, 'Could not set new cost');
        $params = $request->all();
        if (SMSCost::createCost($params)) {
            $result = $this->getResult(false, 'New cost has been set');
        }

        return $result;
    }

    public function getSmsEdit($id)
    {
        $title     = 'Edit SMS cost';
        $model     = SMSCost::find($id);
        $countries = Country::all()->lists('name', 'id');
        $countries = $countries->sort();

        return view('costs.create_edit_sms', compact('title', 'model', 'countries'));
    }

    public function postSmsEdit(Request $request, $id)
    {
        $this->validate($request, [
            'country_id'  => 'required|integer',
            'cents_value' => 'required|numeric',
        ]);
        $result = $this->getResult(true, 'Could not edit cost');
        $model  = SMSCost::find($id);
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'Cost saved successfully');

        return $result;
    }

    public function getSmsDelete($id)
    {
        $title = 'Delete cost ?';
        $model = SMSCost::find($id);
        $url   = Url::to('costs/sms-delete/' . $model->id);

        return view('costs.delete', compact('title', 'model', 'url'));
    }

    public function postSmsDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete cost');
        $model  = SMSCost::find($id);
        if ($model->delete()) {
            $result = $this->getResult(false, 'Cost deleted');
        }

        return $result;
    }

}
