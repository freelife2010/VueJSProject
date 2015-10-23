<?php

namespace App\Http\Controllers;

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
        return view('emails.configure.authorization', compact('title'));
    }


}
