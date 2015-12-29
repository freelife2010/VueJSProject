<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use Dingo\Api\Contract\Http\Request;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\Process\Process;

class VoicemailAPIController extends Controller
{
    use Helpers, APIHelperTrait;

    protected $baseDir = '/mnt/gdrive/108.165.2.110/';

    public function __construct()
    {
        $this->initAPI();
    }


    public function getList($id)
    {
        $id      = preg_replace('/[^0-9]/', '', $id);
        $process = new Process('ls -al ' . $this->baseDir.$id);
        $process->run();

        if (!$process->isSuccessful()) {
            return $process->getErrorOutput();
        }

        return $process->getOutput();
    }

    public function postFile(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $path = $this->baseDir.$request->file;

        return \Illuminate\Support\Facades\Response::download($path, basename($request->file));
    }

}
