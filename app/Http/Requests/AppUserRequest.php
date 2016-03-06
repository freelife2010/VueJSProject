<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class AppUserRequest extends Request
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
            'phone'    => 'required',
            'password' => 'sometimes|required|min:6',
            'email'    => 'required|email|unique:users,email,0,id,deleted_at,NULL'
        ];

        if ($id)
            $rules['email'] = 'sometimes|required|unique:users,email,' . $id;

        return $rules;
    }
}
