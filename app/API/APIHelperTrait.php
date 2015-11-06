<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 05.11.15
 * Time: 19:24
 */

namespace App\API;


use Dingo\Api\Http\Request;
use Illuminate\Support\Collection;
use Validator;

trait APIHelperTrait {

    protected function makeValidator($request, $rules)
    {
        return Validator::make($request->all(), $rules);

    }

    protected function validationFailed($validator)
    {
        return $this->response->errorBadRequest(implode(' ',$validator->errors()->all()));
    }

    protected function defaultResponse($params)
    {
        $request = Request::capture();
        $path    = $request->getPathInfo();
        $defaultParams = [
            'action' => $request->getMethod(),
            'path'   => substr($path, 4, strlen($path)),
            'uri'    => $request->getUri(),
            'params' => [],
            'timestamp' => time()
        ];
        $collection = new Collection(array_merge($params, $defaultParams));

        return $collection;
    }

    protected function getSign($request)
    {
        $accountId  = $request->input('account_id');
        $name       = $request->input('name');
        $sign       = sha1("$accountId&$name&$name&$accountId");

        return $sign;
    }

    private function setGzipHeader()
    {
        $compressedContent       = gzencode($this->response->getContent(), 9, FORCE_GZIP);
        $compressedContentLength = strlen($compressedContent);
        $this->response->header('Content-Encoding', 'gzip');
        $this->response->setContent($compressedContent);
        $this->response->header('Content-Length', $compressedContentLength);
    }

    private function setDeflateHeader()
    {
        $compressedContent       = gzdeflate($this->response->getContent(), 9, FORCE_DEFLATE);
        $compressedContentLength = strlen($compressedContent);
        $this->response->header('Content-Encoding', 'deflate');
        $this->response->setContent($compressedContent);
        $this->response->header('Content-Length', $compressedContentLength);
    }

    private function getOptionalParams()
    {
        return [
            [
                'name'   => 'gzip',
                'method' => 'setGzipHeader'
            ],
            [
                'name'   => 'deflate',
                'method' => 'setDeflateHeader'
            ],
        ];
    }
}