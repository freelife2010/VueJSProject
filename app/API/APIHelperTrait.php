<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 05.11.15
 * Time: 19:24
 */

namespace App\API;


use App\Models\App;
use DB;
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
        $request       = Request::capture();
        $path          = $request->getPathInfo();
        $defaultParams = [
            'action'    => $request->getMethod(),
            'path'      => substr($path, 4, strlen($path)),
            'uri'       => $request->getUri(),
            'params'    => [],
            'timestamp' => time()
        ];

        return $this->response->array(array_merge($params, $defaultParams));
    }

    protected function getSign($request)
    {
        $accountId  = $request->input('account_id');
        $name       = $request->input('name');
        $sign       = sha1("$accountId&$name&$name&$accountId");

        return $sign;
    }

    protected function getAPPIdByAuthHeader()
    {
        $accessToken = $this->getAccessTokenFromHeader();
        $session     = DB::table('oauth_access_tokens')->select(['session_id as id'])
                            ->whereId($accessToken)->first();
        $client      = DB::table('oauth_sessions')->select(['client_id as id'])
                            ->whereId($session->id)->first();
        $app         = DB::table('oauth_clients')->select(['app_id as id'])
                            ->whereId($client->id)->first();

        return $app->id;
    }

    protected function makeErrorResponse($message)
    {
        return $this->response->array(['error' => $message]);
    }

    /**
     * @return string
     */
    protected function getAccessTokenFromHeader()
    {
        $request     = Request::capture();
        $authHeader  = $request->header('Authorization');
        $accessToken = substr($authHeader, 7, strlen($authHeader));

        return $accessToken;
    }

    private function setGzipHeader()
    {
        $content                 = $this->response->getContent();
        $content                 = !is_array($content) ?: json_encode($content);
        $compressedContent       = gzencode($content, 9, FORCE_GZIP);
        $compressedContentLength = strlen($compressedContent);
        $this->response->header('Content-Encoding', 'gzip');
        $this->response->setContent($compressedContent);
        $this->response->header('Content-Length', $compressedContentLength);
    }

    private function setDeflateHeader()
    {
        $content                 = $this->response->getContent();
        $content                 = !is_array($content) ?: json_encode($content);
        $compressedContent       = gzdeflate($content, 9, FORCE_DEFLATE);
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

    private function isMultiDimensionalArray($input)
    {
        return  (isset($input[0]) and is_array($input[0]));
    }
}