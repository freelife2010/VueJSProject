<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRequest;
use App\Http\Requests\DIDRequest;
use App\Models\DID;
use App\Models\DIDActionParameters;
use DB;
use Former\Facades\Former;
use Illuminate\Http\Request;

use App\Http\Requests;
use URL;
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
        $selectFields = [
            'did.id',
            'account_id',
            'owned_by',
            'action_id',
            'created_at',
            'did',
            'state',
            'rate_center',
            'reserve_id',
            'did_action.name'
        ];
        $DIDs = DID::select($selectFields)->whereAppId($this->app->id)
            ->join('did_action', 'action_id', '=' ,'did_action.id')
            ->whereNull('deleted_at');

        return Datatables::of($DIDs)
            ->add_column('actions', function($did) {
                return $did->getActionButtonsWithAPP('did', $this->app);
            })
            ->edit_column('owned_by', function($did) {
                return $did->appUser ? $did->appUser->email : '';
            })
            ->make(true);
    }

    public function getCreate(Request $request)
    {
        $title    = 'Buy DID';
        $did      = new DID();
        $APP      = $this->app;
        $states   = $did->getStates();
        $states   = array_combine($states, $states);
        $actions  = DB::table('did_action')->lists('name', 'id');
        $appUsers = $APP->users()->lists('email', 'id');

        return view('did.create', compact(
            'title',
            'did',
            'states',
            'APP',
            'appUsers',
            'actions'));
    }


    public function postCreate(DIDRequest $request)
    {
        $result = $this->getResult(true, 'Could not buy DID');
        $did    = new DID();
        $response = $did->reserveDID($request->did);
        if (isset($response->reserveId)) {
            $did->fillParams($request, $response->reserveId);
            if ($did->save()) {
                $result = $this->getResult(false, 'DID has been acquired');
                $did->createDIDParameters($request);
            }
        } elseif (isset($response->error))
            $result = $this->getResult(true, 'Error occurred. Error message: '. $response->error);

        return $result;
    }

    public function getEdit($id)
    {
        $title    = 'See DID';
        $APP      = $this->app;
        $model    = DID::find($id);
        $params   = $model->actionParameters()->joinParamTable()->get();
        $actions  = DB::table('did_action')->lists('name', 'id');
        $appUsers = $APP->users()->lists('email', 'id');

        return view('did.edit', compact(
            'title',
            'model',
            'APP',
            'appUsers',
            'actions',
            'params'));
    }

    public function postEdit(DIDRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not edit DID');
        $did    = DID::find($id);

        $did->owned_by  = $request->owned_by;
        $did->action_id = $request->action;
        if ($did->save()) {
            $did->deleteDIDParameters();
            $did->createDIDParameters($request);
            $result = $this->getResult(false, 'DID successfully edited');
        }

        return $result;
    }

    public function getDelete($id)
    {
        $title = 'Delete DID ?';
        $model = DID::find($id);
        $APP   = $this->app;
        $url   = Url::to('did/delete/'.$model->id);
        return view('did.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete DID');
        $model  = DID::find($id);
        if ($model->delete()) {
            $model->deleteDIDParameters();
            $result = $this->getResult(false, 'DID deleted');
        }

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
            $html .= DIDActionParameters::getActionParameterHtml($parameter, $this->app);
        }

        return $html;

    }

}
