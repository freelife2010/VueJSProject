<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRequest;
use App\Models\Conference;
use App\Models\ConferenceLog;

use App\Http\Requests;
use Illuminate\Http\Request;
use URL;
use yajra\Datatables\Datatables;

class ConferenceController extends AppBaseController
{
    public function getLog()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Conference log';
        $subtitle = 'View conference log';

        return view('conferences.log_index', compact('APP', 'title', 'subtitle'));
    }

    public function getData()
    {
        $conferences = Conference::whereAppId($this->app->id);

        return Datatables::of($conferences)
            ->add_column('actions', function($conference) {
                return $conference->getActionButtonsWithAPP('conferences', $this->app);
            })
            ->make(true);
    }

    public function getLogData()
    {
        $conferenceLogEntries = ConferenceLog::all();

        return Datatables::of($conferenceLogEntries)
            ->make(true);
    }

    public function getIndex()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Conferences';
        $subtitle = 'View conference list';

        return view('conferences.index', compact('APP', 'title', 'subtitle'));
    }

    public function getCreate()
    {
        $APP  = $this->app;
        $title  = 'Create new conference';

        return view('conferences.create_edit', compact('model', 'title', 'APP'));
    }

    public function postCreate(Request $request)
    {
        $this->validateInput($request);
        $result = $this->getResult(true, 'Could not create conference');
        if (Conference::create($request->all()))
            $result = $this->getResult(false, 'Conference has been created');

        return $result;
    }

    public function getEdit($id)
    {
        $title = 'Edit conference';
        $model = Conference::find($id);
        $APP   = $this->app;

        return view('conferences.create_edit', compact('title', 'model', 'APP'));
    }

    public function postEdit(Request $request, $id)
    {
        $this->validateInput($request);
        $result = $this->getResult(true, 'Could not edit conference');
        $model  = Conference::find($id);
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'Conference saved successfully');

        return $result;
    }


    public function getDelete($id)
    {
        $title = 'Delete conference ?';
        $model = Conference::find($id);
        $APP   = $this->app;
        $url   = Url::to('conferences/delete/' . $model->id);

        return view('conferences.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete conference');
        $model  = Conference::find($id);
        if ($model->delete()) {
            $result = $this->getResult(false, 'Conference deleted');
        }

        return $result;
    }

    private function validateInput($request)
    {
        $rules = [
            'app_id'          => 'required',
            'host_pin'        => 'required|integer',
            'guest_pin'       => 'required|integer',
            'name'            => 'required|unique:conference,name',
            'greeting_prompt' => 'required'
        ];

        if ($request->id)
            $rules['name'] = 'sometimes|required|unique:conference,name,' . $request->id;

        $this->validate($request, $rules);
    }
}
