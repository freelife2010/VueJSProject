<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Models\AppUser;
use Dingo\Api\Contract\Http\Request;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Process;

class FileAPIController extends Controller
{
    use Helpers, APIHelperTrait;

    protected $baseDir = '/mnt/gdrive/';

    public function __construct()
    {
        $this->initAPI();
    }


    /**
     * @SWG\Get(
     *     path="/api/voicemail/list/{user_id}",
     *     summary="Return voicemail file list",
     *     tags={"files"},
     *     @SWG\Parameter(
     *         description="APP user ID",
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="File list"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @param $user_id
     * @return bool|mixed
     */
    public function getVoicemailList($user_id)
    {
        $id   = preg_replace('/[^0-9]/', '', $user_id);
        $user = AppUser::findOrFail($id);
        $this->baseDir .= '108.165.2.110/';
        $process = new Process('ls -al ' . $this->baseDir . $id);
        $process->run();

        if (!$process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();
    }


    /**
     * @SWG\Get(
     *     path="/api/voicemail/file/{user_id}",
     *     summary="Return voicemail file",
     *     tags={"files"},
     *     @SWG\Parameter(
     *         description="APP user ID",
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
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
     * @param Request $request
     * @param $user_id
     * @return bool|mixed
     */
    public function getVoicemailFile(Request $request, $user_id)
    {
        $validator = $this->makeValidator($request, [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $id   = preg_replace('/[^0-9]/', '', $user_id);
        $user = AppUser::findOrFail($id);
        $this->baseDir .= '108.165.2.110/';
        $path = $this->baseDir . $id . "/$request->name";

        return Response::download($path, basename($request->name));
    }



}
