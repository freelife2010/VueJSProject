<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use Dingo\Api\Contract\Http\Request;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
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
     * Returns voicemail file list
     * @param $id
     * @return string
     */
    public function getList($id)
    {
        $id      = preg_replace('/[^0-9]/', '', $id);
        $this->baseDir .= '108.165.2.110/';
        $process = new Process('ls -al ' . $this->baseDir.$id);
        $process->run();

        if (!$process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();
    }

    /**
     * Returns voicemail file
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function postFile(Request $request, $id)
    {
        $validator = $this->makeValidator($request, [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $id   = preg_replace('/[^0-9]/', '', $id);
        $this->baseDir .= '108.165.2.110/';
        $path = $this->baseDir.$id."/$request->file";

        return \Illuminate\Support\Facades\Response::download($path, basename($request->file));
    }


    /**
     * Returns conference record file
     * @param Request $request
     * @return string
     */
    public function getRecord(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'conf_name' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $this->baseDir .= 'conference/';
        $request->conf_name = str_replace('|', '', $request->conf_name);
        $path               = $this->baseDir . $request->conf_name;
        if ($request->has('name')) {
            $path = $path."/$request->file";
            return \Illuminate\Support\Facades\Response::download($path, basename($request->file));
        }
        $process            = new Process('ls -al ' . $path);
        $process->run();

        if (!$process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();

    }

}
