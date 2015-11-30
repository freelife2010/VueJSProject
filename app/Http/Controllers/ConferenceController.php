<?php

namespace App\Http\Controllers;

use App\Models\ConferenceLog;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use yajra\Datatables\Datatables;

class ConferenceController extends AppBaseController
{
    public function getLog()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Conference log';
        $subtitle = 'View conference log';

        return view('conferences.log_index', compact('APP', 'title', 'subtitle'));
    }

    public function getData()
    {
        $conferenceLogEntries = ConferenceLog::all();

        return Datatables::of($conferenceLogEntries)
            ->make(true);
    }
}
