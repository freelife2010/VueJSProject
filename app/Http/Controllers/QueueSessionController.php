<?php

namespace App\Http\Controllers;

use App\Models\QueueAgentSession;

use App\Http\Requests;
use App\Models\QueueCallerSession;
use yajra\Datatables\Datatables;

class QueueSessionController extends AppBaseController
{
    public function getAgentLog()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Queue agent session log';
        $subtitle = 'View queue agent session log';

        return view('queues.agent_log', compact('APP', 'title', 'subtitle'));
    }

    public function getAgentData()
    {
        $conferenceLogEntries = QueueAgentSession::all();

        return Datatables::of($conferenceLogEntries)
            ->make(true);
    }

    public function getCallerLog()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': Queue caller session log';
        $subtitle = 'View queue caller session log';

        return view('queues.caller_log', compact('APP', 'title', 'subtitle'));
    }

    public function getCallerData()
    {
        $conferenceLogEntries = QueueCallerSession::all();

        return Datatables::of($conferenceLogEntries)
            ->make(true);
    }
}
