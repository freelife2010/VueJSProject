<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRequest;
use App\Models\Queue;
use Illuminate\Http\Request;

use App\Http\Requests;
use URL;
use Yajra\Datatables\Datatables;

class QueueController extends AppBaseController
{

    public function getData()
    {
        $conferences = Queue::whereAppId($this->app->id);

        return Datatables::of($conferences)
            ->add_column('actions', function($conference) {
                return $conference->getActionButtonsWithAPP('queues', $this->app);
            })
            ->make(true);
    }

    public function getIndex()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Queues';
        $subtitle = 'View queue list';

        return view('queues.index', compact('APP', 'title', 'subtitle'));
    }

    public function getCreate()
    {
        $APP   = $this->app;
        $title = 'Create new queue';

        return view('queues.create_edit', compact('model', 'title', 'APP'));
    }

    public function postCreate(Request $request)
    {
        $this->validateInput($request);
        $result = $this->getResult(true, 'Could not create queue');
        if (Queue::create($request->all()))
            $result = $this->getResult(false, 'Queue has been created');

        return $result;
    }

    public function getEdit($id)
    {
        $title = 'Edit conference';
        $model = Queue::find($id);
        $APP   = $this->app;

        return view('queues.create_edit', compact('title', 'model', 'APP'));
    }

    public function postEdit(Request $request, $id)
    {
        $this->validateInput($request);
        $result = $this->getResult(true, 'Could not edit queue');
        $model  = Queue::find($id);
        if ($model->fill($request->input())
            and $model->save()
        )
            $result = $this->getResult(false, 'Queue saved successfully');

        return $result;
    }


    public function getDelete($id)
    {
        $title = 'Delete queue ?';
        $model = Queue::find($id);
        $APP   = $this->app;
        $url   = Url::to('queues/delete/' . $model->id);

        return view('queues.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete queue');
        $model  = Queue::find($id);
        if ($model->delete()) {
            $result = $this->getResult(false, 'Queue deleted');
        }

        return $result;
    }

    private function validateInput($request)
    {
        $this->validate($request, [
            'app_id'                => 'required',
            'queue_name'            => 'required|unique::queue,queue_name',
            'client_waiting_prompt' => 'required',
            'agent_waiting_prompt'  => 'required'
        ]);
    }

}
