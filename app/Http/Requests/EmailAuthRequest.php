<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class EmailAuthRequest extends Request
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
            'smtp_host'    => 'required|host',
            'smtp_port'    => 'required|numeric',
            'from_name'    => 'required',
            'from_address' => 'required',
            'content'      => 'required',
            'subject'      => 'required|max:255'
        ];
    }
}
