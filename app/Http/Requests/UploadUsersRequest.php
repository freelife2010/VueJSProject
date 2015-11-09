<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UploadUsersRequest extends Request
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
        return [
            'sheet_file' => 'required|max:5000',
            'email'      => 'required',
            'username'   => 'required',
            'password'   => 'required',
            'app_id'     => 'required',
        ];
    }
}
