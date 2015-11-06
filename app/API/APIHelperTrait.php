<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 05.11.15
 * Time: 19:24
 */

namespace App\API;


use Dingo\Api\Http\Request;
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

    protected function defaultResponse($request, $params)
    {
        $defaultParams = [
            'action' => $request->getMethod(),
            'path'   => dirname($request->getPathInfo()),
            'uri'    => $request->getUri(),
            'params' => [],
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
}