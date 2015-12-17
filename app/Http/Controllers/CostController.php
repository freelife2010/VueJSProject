<?php

namespace App\Http\Controllers;


use App\Http\Requests;
use App\Http\Requests\DeleteRequest;
use App\Models\DID;
use App\Models\DIDCost;
use Former;
use Illuminate\Http\Request;
use URL;
use yajra\Datatables\Datatables;

class CostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDid()
    {
        $title    = 'DID cost';
        $subtitle = 'Modify DID cost';

        return view('costs.did', compact('title', 'subtitle'));
    }

    public function getDidData()
    {
        $didCosts = DIDCost::all();


        return Datatables::of($didCosts)
            ->add_column('actions', function ($cost) {
                $urls['edit'] = url('costs/did-edit/'.$cost->id);
                $urls['delete'] = url('costs/did-delete/'.$cost->id);
                return $cost->getDefaultActionButtons('', $urls);
            })
            ->make(true);
    }

    public function getDidCreate()
    {
        $title = 'Set new cost';
        $did      = new DID();
        $states   = $did->getStates();
        $states   = array_combine($states, $states);

        return view('costs.create_edit_did', compact('title', 'states'));
    }

    public function getDidCities(Request $request)
    {
        $state       = $request->state;
        $did         = new DID();
        $rateCenters = $did->getNPA($state);
        $rateCenters = $did->getList($rateCenters, 'RateCenter');

        return Former::select('rate_center')->options($rateCenters)->raw();

    }

    public function postDidCreate(Request $request)
    {
        $this->validate($request, [
            'state'       => 'required',
            'rate_center' => 'required',
            'value'       => 'required|numeric'
        ]);
        $result = $this->getResult(true, 'Could not set new cost');
        $params = $request->all();
        $params['rate_center'] = $params['rate_center'] != 0 ?: 'All';
        if ($user = DIDCost::create($params)) {
            $result = $this->getResult(false, 'New cost has been set');
        }

        return $result;
    }

    public function getDidEdit($id)
    {
        $title  = 'Edit cost';
        $model  = DIDCost::find($id);
        $did    = new DID();
        $states = $did->getStates();

        return view('costs.create_edit_did', compact('title', 'model', 'states'));
    }

    public function postDidEdit(Request $request, $id)
    {
        $this->validate($request, [
            'state'       => 'required',
            'rate_center' => 'required',
            'value'       => 'required|numeric'
        ]);
        $result = $this->getResult(true, 'Could not edit cost');
        $model  = DIDCost::find($id);
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

        return view('costs.delete_did', compact('title', 'model', 'url'));
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

}
