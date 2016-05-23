<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 26.12.15
 * Time: 16:59
 */

namespace App\API;


use App\Models\AppUser;

class DIDXMLActionBuilder
{
    protected $did = null;
    protected $condition = null;

    public function __construct($did, $condition)
    {
        $this->did = $did;
        $this->condition = $condition;
    }


    public function build()
    {
        $actionName      = $this->did->name;
        $actionParameter = $this->did->actionParameters()->joinParamTable()->first();
        $actionParameter = $actionParameter ? $actionParameter->parameter_value : '';

        switch($actionName) {
            case 'Conference':
                $actionName = 'conference';
                $actionSet = $this->condition->addChild('action');
                $actionSet->addAttribute('application', 'set');
                $value = sprintf(
                    'auto-record=/mnt/gdrive/conference/%s/%s.wav',
                        $actionParameter,
                        strftime("%Y-%m-%d-%H-%M-%S"));
                $actionSet->addAttribute('data', $value);
                break;
            case 'Forward to user':
                $opensips_ip = env('OPENSIPS_IP', '158.69.203.191');
                $actionName   = 'bridge';
                $actionParameter = "sofia/internal/$actionParameter@$opensips_ip:5060";
                break;
            case 'Forward to number':
                $actionName = 'bridge';
                $user = $this->did->appUser;
                $techPrefix = $user ? $user->tech_prefix : '';
                $callerId = "[effective_caller_id_number=$user->caller_id]";
                $actionParameter = "{$callerId}sofia/internal/$techPrefix$actionParameter@69.27.168.11";
                break;
            case 'Voicemail':
                $actionName = 'voicemail';
                $actionAnswer = $this->condition->addChild('action');
                $actionAnswer->addAttribute('application', 'answer');
                $actionSet = $this->condition->addChild('action');
                $actionSet->addAttribute('application', 'set');
                $actionSet->addAttribute('data', 'skip_greeting=true');
                $sipAccount = '';
                if ($actionParameter) {
                    $user = AppUser::find($actionParameter);
                    $sipAccount = $user->getDefaultSipAccount();
                }
                $actionParameter = 'default 108.165.2.110 '. $sipAccount;
                break;
            case 'Stream Audio':
                $actionName = 'playback';
                $this->did->name = 'playback';
                $actionParameter = "vlc://$actionParameter";
                break;
            case 'IVR':
                $actionName = 'play_and_get_digits';
                $jsonParam = $this->did->actionParameters()->joinParamTable()
                    ->whereName('Key-Action')->first();
                $actionParameter = $jsonParam->getIVROptionsDataString();
                $transferAction = $this->condition->addChild('action');
                $transferAction->addAttribute('application', 'tranfer');
                $transferAction->addAttribute('data', 'ivr_handling XML default');
                break;
            case 'Queue':
                $actionName = 'fifo';
                $appId      = $this->did->appUser->app_id;
                $actionParameter .= $this->did->id."-$appId".' in';
                break;
            case 'Dequeue':
                $actionName = 'fifo';
                $actionParameter .= $this->did->id.' out';
                break;
            case 'Playback TTS':
                $actionName = 'playback';
                $actionParameter = url('/voice/'.$actionParameter);
                break;
            case 'Playback URL':
                $actionName = 'playback';
                $this->appendAction('answer');
                break;
            case 'Playback File':
                $actionName = 'playback';
                break;
            case 'Hang Up':
                $actionName = 'hangup';
                break;
            default:
                break;
        }

        $this->appendAction($actionName, $actionParameter);
    }

    private function appendAction($applicationName, $parameter = null)
    {
        $action = $this->condition->addChild('action');
        $action->addAttribute('application', $applicationName);
        if ($parameter)
            $action->addAttribute('data', $parameter);
    }


}