<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 05.11.15
 * Time: 19:24
 */

namespace App\API;


use App\Helpers\APILogger;
use Config;
use DateTime;
use DateTimeZone;
use DB;
use Dingo\Api\Http\Request;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Moment\CustomFormats\MomentJs;
use Moment\Moment;
use Validator;

trait APIHelperTrait
{

    protected $request;

    function initAPI()
    {
        $this->request = Request::capture();
        if ($this->request->has('datetz'))
            Config::set('app.timezone', $this->request->input('datetz'));
        APILogger::log($this->request->all(), 'API Request');
    }

    protected function makeValidator($request, $rules)
    {
        return Validator::make($request->all(), $rules);
    }

    protected function setValidator($rules)
    {
        $validator = $this->makeValidator($this->request, $rules);
        if ($validator->fails()) {
            throw new \Exception(implode(' ', $validator->errors()->all()));
        }
    }

    protected function validationFailed($validator)
    {
        return $this->response->errorBadRequest(implode(' ', $validator->errors()->all()));
    }

    /**
     * Returns query builder
     * @param $className Entity class name
     * @param $fields select fields
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getEntities($className, $fields)
    {
        $className = '\\App\Models\\' . $className;
        $entities  = $className::select($fields);
        if ($this->request->has('skip'))
            $entities = $entities->skip($this->request->input('skip'));
        if ($this->request->has('length'))
            $entities = $entities->take($this->request->input('length'));

        return $entities;
    }

    protected function defaultResponse($params)
    {
        $path = $this->request->getPathInfo();
        if (isset($params['entities'])
            and $this->request->has('datetz')
        )
            $this->setTimezones($params['entities'], $this->request->input('datetz'));
        $defaultParams = [
            'action'    => $this->request->getMethod(),
            'path'      => substr($path, 4, strlen($path)),
            'uri'       => $this->request->getUri(),
            'params'    => $this->request->all(),
            'timestamp' => time()
        ];

        $response = $this->response->array(array_merge($defaultParams, $params));
        APILogger::log($response, 'API Response');

        return $response;
    }

    protected function getSign($request)
    {
        $accountId = $request->input('account_id');
        $name      = $request->input('name');
        $sign      = sha1("$accountId&$name&$name&$accountId");

        return $sign;
    }

    protected function getAPPIdByAuthHeader()
    {
        $oauthClient = DB::table('oauth_clients')
            ->whereId(Authorizer::getResourceOwnerId())
            ->first();
        $appId       = $oauthClient->app_id;

        return $appId;
    }

    protected function makeResponse($params)
    {
        $response = $this->response->array($params);
        APILogger::log($response, 'API Response');

        return $response;
    }

    protected function makeErrorResponse($message)
    {
        $response = $this->response->array(['error' => $message]);
        APILogger::log($response, 'API Response');

        return $response;
    }

    /**
     * @return string
     */
    protected function getAccessTokenFromHeader()
    {
        $authHeader  = $this->request->header('Authorization');
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

    private function setTimezones($entities, $timezone)
    {
        $timestamps = ['created', 'modified'];
        foreach ($entities as $entity) {
            foreach ($timestamps as $timestamp) {
                $date = new DateTime($entity->$timestamp, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone($timezone));
                $entity->$timestamp = $this->getDateFormat($date);
            }
        }
    }

    private function getDateFormat($date)
    {
        $format = 'Y-m-d H:i:s';
        if ($this->request->has('dateformat')) {
            $date   = new Moment($date->format($format));
            $format = $this->request->input('dateformat');
            $date   = $date->format($format, new MomentJs());
        } else $date = $date->format($format);

        return $date;
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
        return (isset($input[0]) and is_array($input[0]));
    }
}