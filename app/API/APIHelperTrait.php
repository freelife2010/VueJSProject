<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 05.11.15
 * Time: 19:24
 */

namespace App\API;


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
}