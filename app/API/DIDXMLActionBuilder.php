<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 26.12.15
 * Time: 16:59
 */

namespace App\API;


use App\Helpers\Misc;
use App\Models\AppUser;
use App\Models\Conference;

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
                $conference = Conference::find($actionParameter);
                if ($conference)
                    $actionParameter = "$conference->name+$conference->guest_pin";
                $value = sprintf(
                    'auto-record=/mnt/gdrive/conference/%s/%s.wav',
                        $actionParameter,
                        strftime("%Y-%m-%d-%H-%M-%S"));
                $actionSet->addAttribute('data', $value);
                break;
            case 'Forward To User':
                $actionName   = 'bridge';
                $user = $this->did->appUser;
                $userId = $user ? Misc::filterNumbers($user->getUserAlias()) : '';
                $actionParameter = "sofia/internal/$userId$actionParameter@69.27.168.11";
                break;
            case 'Forward To Number':
                $actionName = 'bridge';
                $user = $this->did->appUser;
                $userId = $user ? Misc::filterNumbers($user->getUserAlias()) : '';
                $actionParameter = "sofia/internal/" . $userId . "9" . $actionParameter . "@69.27.168.11";
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
                $this->makeQueueXmlResponse();
                return;
                break;
            case 'Dequeue':
                $actionName = 'fifo';
                $actionParameter .= $this->did->id.' out';
                break;
            case 'Playback TTS':
                $actionName = 'playback';
                $this->appendAction('answer');
                $actionParameter = url('/voice/'.$actionParameter);
                break;
            case 'Playback URL':
                $actionName = 'playback';
                $this->appendAction('answer');
                break;
            case 'Playback File':
                $this->appendAction('answer');
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

    private function makeQueueXmlResponse()
    {
        $actionName    = 'fifo';
        $appId         = $this->did->appUser->app_id;
        $parameters    = $this->did->actionParameters()->joinParamTable()->get();
        $alias         = '';
        $goodbyePrompt = '';
        $this->appendAction('answer');
        foreach ($parameters as $parameter) {
            if ($parameter->name == 'Alias of the queue')
                $alias = $parameter->parameter_value;
            if ($parameter->name == 'Welcome Prompt' and $parameter->parameter_value)
                $this->appendAction('playback', $parameter->parameter_value);
            if ($parameter->name == 'Background Music' and $parameter->parameter_value)
                $this->appendAction('set', "fifo_music=$parameter->parameter_value");
            if ($parameter->name == 'Goodbye Prompt')
                $goodbyePrompt = $parameter->parameter_value;

        }
        $alias .= $this->did->id . "-$appId" . ' in';
        $this->appendAction($actionName, $alias);

        if ($goodbyePrompt)
            $this->appendAction('playback', $goodbyePrompt);
    }


}