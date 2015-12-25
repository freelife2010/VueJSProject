<?php

namespace App\Http\Controllers;

use App\Helpers\PlaySMSTrait;

use App\Http\Requests;
use App\Models\SMS;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use yajra\Datatables\Datatables;

class SMSController extends AppBaseController
{
    use PlaySMSTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getInbox()
    {
        $title    = 'SMS inbox';
        $APP      = $this->app;
        $subtitle = 'View SMS Inbox';

        return view('sms.inbox', compact('title', 'subtitle', 'APP'));
    }

    public function getData()
    {
        $smsHistory = $this->checkSMSInbox();
        $smsHistory = isset($smsHistory['data']) ? new Collection($smsHistory['data']) : new Collection();

        return Datatables::of($smsHistory)
            ->edit_column('dt', function($entry) {
                return date('d.m.Y H:i:s', strtotime($entry['dt']));
            })
            ->make(true);
    }

    public function getSend()
    {
        $APP   = $this->app;
        $title = 'Send sms';
        $users = $APP->users()->lists('email', 'id');

        return view('sms.send', compact('model', 'title', 'APP', 'users'));
    }

    public function postSend(Request $request)
    {
        $this->validateInput($request);
        $sms    = new SMS();
        $sms->setUsers($request->users);
        $totalCost = $sms->getTotalCost();
        $user      = Auth::user();
        if (!$user->hasSum($totalCost)) {
            $clientBalance = $user->getClientBalance();
            $result        = $this->getResult(true,
                'Could not send SMS: not enough balance<br/>
                Total SMS cost: ' . $totalCost . '<br/>
                Current balance: ' . $clientBalance. '<br/>
                Client ID: '.$user->clientId);
        } else {
            $totalSent = $sms->sendMessage($request->message);
            $user->deductSMSCost($totalSent);
            $result = $this->getSMSSentResult($totalSent);
        }

        return $result;
    }

    protected function getSMSSentResult($totalSent)
    {
        $sentString = '';
        foreach ($totalSent as $data) {
            $sentString .= sprintf('%s - %s<br/>', $data['user'], $data['error'] ?: 'sent');
        }

        $totalCost = isset ($data['totalCost']) ? sprintf("Total cost: %s", $data['totalCost']) : '';

        return $this->getResult(false, 'Operation completed. Total sent: <br/>'.$sentString.$totalCost);
    }

    private function validateInput($request)
    {
        $this->validate($request, [
            'app_id'  => 'required',
            'users'   => 'required',
            'message' => 'required'
        ]);
    }
}
