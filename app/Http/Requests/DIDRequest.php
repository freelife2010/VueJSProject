<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Models\DIDActionParameters;

class DIDRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'did'            => 'required_without:outside_number',
            'owned_by'       => 'required',
            'action'         => 'required'
        ];

        if ($this->parameters) {
            $paramId = DIDActionParameters::getJsonParamId($this->parameters);
            if ($paramId)
                $rules['parameters.'.$paramId] = 'json';
        }

        return $rules;
    }
}
