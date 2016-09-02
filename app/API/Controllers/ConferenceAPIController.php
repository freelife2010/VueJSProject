<?php

namespace App\API\Controllers;

use App\Models\App;
use App\Models\Conference;

use App\Http\Requests;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Response;

class ConferenceAPIController extends FileAPIController
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @SWG\Get(
     *     path="/api/conference/conference-list",
     *     summary="Get list of conferences",
     *     tags={"conferences"},
     *     @SWG\Response(response="200", description="List of conferences"),
     *     @SWG\Response(response="400", description="Bad request"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getConferenceList()
    {
        $appId = $this->getAPPIdByAuthHeader();
        $app   = App::findOrFail($appId);

        return $this->defaultResponse(['conferences' => $app->conferences->pluck('name')]);
    }

    /**
     * @SWG\Get(
     *     path="/api/conference/show/{conferenceName}",
     *     summary="Get list of conferences",
     *     tags={"conferences"},
     *     @SWG\Parameter(
     *         description="Conference name",
     *         name="conferenceName",
     *         in="path",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Conference"),
     *     @SWG\Response(response="400", description="Bad request"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getShow($conferenceName)
    {
        $appId      = $this->getAPPIdByAuthHeader();
        $app        = App::findOrFail($appId);
        $conference = $app->conferences()->whereName($conferenceName)->first();
        if ($conference)
            return $this->defaultResponse(['conference' => $conference]);
        else return $this->response->errorNotFound('Conference not found');
    }

    /**
     * @SWG\Get(
     *     path="/api/conference/list/{user_id}",
     *     summary="Return conference file list",
     *     tags={"files"},
     *     @SWG\Parameter(
     *         description="APP user ID",
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         description="Conference name",
     *         name="conf_name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="File list"),
     *     @SWG\Response(response="400", description="Bad request"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param $user_id
     * @return bool|mixed
     */
    public function getList($user_id)
    {
        $this->setValidator([
            'conf_name' => 'required',
        ]);
        $id = preg_replace('/[^0-9]/', '', $user_id);

        $this->baseDir .= 'conference/';
        $this->request->conf_name = str_replace('|', '', $this->request->conf_name);
        $path                     = $this->baseDir . $this->request->conf_name;
        $process                  = new Process('ls -al ' . $path . "/");
        $process->run();

        if (!$process->isSuccessful()) {
            return $this->response->errorBadRequest($process->getErrorOutput());
		// return "ok";
        }

        return $process->getOutput();
    }

    /**
     * @SWG\Get(
     *     path="/api/conference/file/{user_id}",
     *     summary="Return conference record file",
     *     tags={"files"},
     *     @SWG\Parameter(
     *         description="APP user ID",
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Conference name",
     *         name="conf_name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="File name",
     *         name="name",
     *         in="query",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="File"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param $user_id
     * @return bool|mixed
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

    /**
     * @SWG\Post(
     *     path="/api/conference/add",
     *     summary="Create conference",
     *     tags={"conferences"},
     *     @SWG\Parameter(
     *         description="Conference name",
     *         name="name",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Host PIN",
     *         name="host_pin",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Guest PIN",
     *         name="guest_pin",
     *         in="formData",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Greeting prompt",
     *         name="greeting_prompt",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function postAdd()
    {
        $this->setValidator([
            'name'            => 'required|string',
            'host_pin'        => 'required|string|size:4',
            'guest_pin'       => 'required|string|size:4',
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

    /**
     * @SWG\Post(
     *     path="/api/conference/delete",
     *     summary="Delete conference",
     *     tags={"conferences"},
     *     @SWG\Parameter(
     *         description="Conference name",
     *         name="name",
     *         in="formData",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
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
