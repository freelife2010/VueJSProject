<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class DeveloperRequest extends Request
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
        $id    = $this->request->get("id");
        $rules = [
            'name'     => 'required',
            'password' => 'sometimes|required|min:6',
            'email'    => 'required|email|unique:accounts,email,0,id,deleted_at,NULL'
        ];

        if ($id)
            $rules['email'] = 'sometimes|required|unique:accounts,email,' . $id;

        return $rules;
    }
}
