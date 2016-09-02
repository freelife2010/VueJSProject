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

        $downloadButton = sprintf('
                        <a href="%s"
                           title="Download"
                           target="_blank"
                           class="btn btn-info btn-sm" style="margin-top: -25px;">
                            <span class="fa fa-download"></span></a>
                    ', URL::to('queues/download/{conference-id}/{type}?app=' . $this->app->id));

        return Datatables::of($conferences)
            ->edit_column('client_waiting_prompt', function ($conference) use ($downloadButton){
                if (empty($conference->client_waiting_audio))
                    return $conference->client_waiting_prompt;
                return "<audio controls>
                        <source src='/audio/" . $this->app->id . "-client/" . $conference->client_waiting_audio . "' type='audio/mpeg'>
                        Your browser does not support the audio element.
                    </audio>" . str_replace(['{conference-id}', '{type}'], [$conference->id, 'client'], $downloadButton);
            })
            ->edit_column('agent_waiting_prompt', function ($conference) use ($downloadButton){
                if (empty($conference->agent_waiting_audio))
                    return $conference->agent_waiting_prompt;
                return
                    "<audio controls>
                        <source src='/audio/" . $this->app->id . "-agent/" . $conference->agent_waiting_audio . "' type='audio/mpeg'>
                        Your browser does not support the audio element.
                    </audio>" . str_replace(['{conference-id}', '{type}'], [$conference->id, 'agent'], $downloadButton);
            })
            ->add_column('actions', function ($conference) {
                $html = $conference->getActionButtonsWithAPP('queues', $this->app);
                $html .= sprintf('
                        <a href="%s"
                           data-target="#myModal"
                           data-toggle="modal"
                           title="Upload/Replace"
                           class="btn btn-info btn-sm" >
                            <span class="fa fa-upload"></span></a>
                    ', URL::to('queues/upload/' . $conference->id . '?app=' . $this->app->id));
                return $html;
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
        if ($queue = Queue::create($request->all()))
//            $result = $this->getResult(false, "Queue [$queue->queue_name] has been created");
	    $last_queue = Queue::orderby('id', 'desc')->take(1)->first();
	    $queueId = $last_queue['id'];

//$result = $this->getResult(false, $appId);
//return $result;

        /**
	 * Add additional feature for video uploading in creating
	 *
	 */
	$appId = $request->app_id;
        if (!is_dir(public_path() . '/audio/' . $appId . '-client/')) {
            mkdir(public_path() . '/audio/' . $appId . '-client/', 0777, true);
        }
        if (!is_dir(public_path() . '/audio/' . $appId . '-agent/')) {
            mkdir(public_path() . '/audio/' . $appId . '-agent/', 0777, true);
        }

        if ($request->file('client_waiting_audio')) {
	
		$result = $this->getResult(false, "There is audio file.");
		return $result;	

            $fileName = date('YmdHis') . '.' . $request->file('client_waiting_audio')->getClientOriginalExtension();
            $request->file('client_waiting_audio')->move(
                base_path() . '/public/audio/' . $appId . '-client/', $fileName
            );
            Queue::find($queueId)->update(['client_waiting_audio' => $fileName]);
        }
        if ($request->file('agent_waiting_audio')) {
            $fileName = date('YmdHis') . '.' . $request->file('agent_waiting_audio')->getClientOriginalExtension();
            $request->file('agent_waiting_audio')->move(
                base_path() . '/public/audio/' . $appId . '-agent/', $fileName
            );
            Queue::find($queueId)->update(['agent_waiting_audio' => $fileName]);
        }

	$result = $this->getResult(false, "A new queue has been created");
	
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
            $result = $this->getResult(false, "Queue [$model->queue_name] deleted");
        }

        return $result;
    }

    private function validateInput($request)
    {
        $id    = $request->get("id");
        $rules = [
            'app_id'                => 'required',
            'queue_name'            => 'required|unique:queue,queue_name',
            'client_waiting_prompt' => 'required',
            'agent_waiting_prompt'  => 'required'
        ];
        if ($id)
            $rules['queue_name'] = 'sometimes|required|unique:queue,queue_name,' . $id;
        $this->validate($request, $rules);
    }

    public function getUpload($id)
    {
        $APP   = $this->app;
        $title = 'Upload Audio';

        return view('queues.upload', compact('id', 'title', 'APP'));
    }

    public function postUpload(Request $request)
    {
        $appId = $request->app_id;
        if (!is_dir(public_path() . '/audio/' . $appId . '-client/')) {
            mkdir(public_path() . '/audio/' . $appId . '-client/', 0777, true);
        }
        if (!is_dir(public_path() . '/audio/' . $appId . '-agent/')) {
            mkdir(public_path() . '/audio/' . $appId . '-agent/', 0777, true);
        }

        if ($request->file('client_waiting_audio')) {
	
//	    $result = $this->getResult(false, "There is audio file.");
//	    return $result;

            $fileName = date('YmdHis') . '.' . $request->file('client_waiting_audio')->getClientOriginalExtension();
            $request->file('client_waiting_audio')->move(
                base_path() . '/public/audio/' . $appId . '-client/', $fileName
            );
            Queue::find($request->id)->update(['client_waiting_audio' => $fileName]);
        }
        if ($request->file('agent_waiting_audio')) {
            $fileName = date('YmdHis') . '.' . $request->file('agent_waiting_audio')->getClientOriginalExtension();
            $request->file('agent_waiting_audio')->move(
                base_path() . '/public/audio/' . $appId . '-agent/', $fileName
            );
            Queue::find($request->id)->update(['agent_waiting_audio' => $fileName]);
        }

        return back();
    }

    public function getDownload($id, $type)
    {
        if (in_array($type, ['client', 'agent'])) {
            $queue = Queue::find($id);
            $filename = $type == 'client' ? $queue->client_waiting_audio : $queue->agent_waiting_audio;
            return \Response::download(public_path() . '/audio/' . $this->app->id . '-' . $type . '/' . $filename);
        }
    }

}
