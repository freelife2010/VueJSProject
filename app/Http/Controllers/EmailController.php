<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailAuthRequest;
use App\Models\Email;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        return redirect('emails/auth-content');
    }

    public function getAuthContent()
    {
        $title = 'Authorization e-mail';
        $model = Email::whereType('authorization')->first();

        return view('emails.configure.authorization', compact('title', 'model'));
    }

    public function postAuthContent(EmailAuthRequest $request, $id)
    {
        $result = $this->getResult(true, 'Failed to save e-mail form');
        $model  = Email::find($id);
        if ($model->fill($request->input()) and $model->save())
            $result = $this->getResult(false, 'E-mail saved');

        return $result;
    }


}
