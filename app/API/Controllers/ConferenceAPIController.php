<?php

namespace App\API\Controllers;

use App\Models\Conference;
use Dingo\Api\Contract\Http\Request;

use App\Http\Requests;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Response;

class ConferenceAPIController extends FileAPIController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getList($user_id)
    {
        $this->setValidator([
            'conf_name' => 'required',
        ]);
        $id = preg_replace('/[^0-9]/', '', $user_id);

        $this->baseDir .= 'conference/';
        $this->request->conf_name = str_replace('|', '', $this->request->conf_name);
        $path                     = $this->baseDir . $this->request->conf_name;
        $process                  = new Process('ls -al ' . $path);
        $process->run();

        if (!$process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();
    }

    /**
     * Returns conference record file
     * @return string
     */
    public function getFile($user_id)
    {
        $request = $this->request;
        $this->setValidator([
            'conf_name' => 'required',
            'name'      => 'required'
        ]);
        $this->baseDir .= 'conference/';
        $request->conf_name = str_replace('|', '', $request->conf_name);
        $path               = $this->baseDir . $request->conf_name . "/$request->name";

        return Response::download($path, basename($request->name));

    }

    public function postAdd()
    {
        $this->setValidator([
            'name'            => 'required',
            'host_pin'        => 'required',
            'guest_pin'       => 'required',
            'greeting_prompt' => 'required'
        ]);

        $appId            = $this->getAPPIdByAuthHeader();
        $params           = $this->request->all();
        $params['app_id'] = $appId;

        $response = $this->makeErrorResponse('Failed to create conference');

        if (Conference::create($params))
            $response = $this->defaultResponse(['result' => 'Conference has been created']);

        return $response;
    }

    public function postDelete()
    {
        $this->setValidator([
            'name' => 'required|exists:conference,name',
        ]);

        $appId       = $this->getAPPIdByAuthHeader();
        $response    = $this->makeErrorResponse('Failed to delete conference');
        $conferences = Conference::whereAppId($appId)->whereName($this->request->name)->get();

        if ($conferences) {
            foreach ($conferences as $conference) {
                $conference->delete();
            }

            $response = $this->defaultResponse(['result' => 'Conference has been deleted']);
        }

        return $response;
    }
}
