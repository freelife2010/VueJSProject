<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailAuthRequest;
use App\Models\Email;
use App\Http\Requests;

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
        $title     = 'Authorization e-mail';
        $subtitle  = 'Modify authorization e-mail content';
        $model     = Email::whereType('authorization')->first();
        $actionUrl = url("emails/auth-content/$model->id");

        return view('emails.configure', compact(
            'title',
            'model',
            'actionUrl',
            'subtitle'));
    }

    public function postAuthContent(EmailAuthRequest $request, $id)
    {
        $result = $this->getResult(true, 'Failed to save e-mail form');
        $model  = Email::find($id);
        if ($model->fill($request->input()) and $model->save())
            $result = $this->getResult(false, 'E-mail saved');

        return $result;
    }

    public function getConfirmContent()
    {
        $title     = 'Confirmation e-mail';
        $subtitle  = 'Modify confirmation e-mail content';
        $model     = Email::whereType('confirmation')->first();
        $actionUrl = url("emails/confirm-content/$model->id");

        return view('emails.configure', compact(
            'title',
            'model',
            'actionUrl',
            'subtitle'));
    }

    public function postConfirmContent(EmailAuthRequest $request, $id)
    {
        $result = $this->getResult(true, 'Failed to save e-mail form');
        $model  = Email::find($id);
        if ($model->fill($request->input()) and $model->save())
            $result = $this->getResult(false, 'E-mail saved');

        return $result;
    }


}
