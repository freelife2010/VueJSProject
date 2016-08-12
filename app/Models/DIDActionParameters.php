<?php

namespace App\Models;


use DB;
use Former\Facades\Former;
use Validator;

class DIDActionParameters extends BaseModel
{
    protected $table = 'did_actions_parameters';

    public function scopeJoinParamTable($query)
    {
        return $query->join('did_action_parameters', 'parameter_id', '=', 'did_action_parameters.id');
    }

    public static function getIVROptions()
    {
        $options = [
            'min'                 => 1,
            'max'                 => 2,
            'tries'               => 3,
            'timeout'             => 7000,
            'terminators'         => '#',
            'file'                => '/conf-pin.wav',
            'invalid_file'        => '/invalid.wav',
            'digit_timeout'       => 2000,
            'transfer_on_failure' => 'freeswitch_action'
        ];

        return json_encode($options);
    }

    public function getIVROptionsDataString()
    {
        $options = json_decode($this->parameter_value);

        $data = sprintf('%s %s %s %s %s %s %s dtmf \d+ %s %s XML default',
            $options->min,
            $options->max,
            $options->tries,
            $options->timeout,
            $options->terminators,
            $options->file,
            $options->invalid_file,
            $options->digit_timeout,
            $options->transfer_on_failure);

        return $data;
    }

    public static function getActionParameterHtml($parameter, $app, $action)
    {
        $selectName = "parameters[$parameter->id]";
        $paramName  = (strpos($parameter->name, 'APP user id') !== false ) ?
                                                'APP user id':
                                                $parameter->name;
        switch ($paramName) {
            case 'APP user id':
                $users = AppUser::whereAppId($app->id)->lists('name', 'id');
                $html  = Former::select($selectName)->options($users)
                    ->placeholder($parameter->name)->label('')->required();
                break;
            case 'Key-Action':
                $IVROptions = self::getIVROptions();
                $html       = Former::textarea($selectName)->required()
                    ->placeholder($parameter->name)->rows(5)
                    ->value($IVROptions)->raw();
                $html .= '<span class="help-block">Options in JSON</span>';
                break;
            case 'Conference Alias':
                $conferences = Conference::whereAppId($app->id)->lists('name', 'id');
                $html  = Former::select($selectName)->options($conferences)
                    ->placeholder($parameter->name)->label('')->required();
                break;

            default:
                $html = Former::text($selectName)->required()
                        ->placeholder($parameter->name)->raw() . '<br/>';

        }

        if ($paramName == 'APP user id'
            and $action
            and $action->name == 'Forward To User')
            $html = self::getForwardToUserParameterHtml($action, $parameter, $app);

        return $html;
    }

    public static function getForwardToUserParameterHtml($action, $parameter, $app)
    {
        $users = AppUser::whereAppId($app->id)->lists('name', 'id');
        $html = Former::label('App User');
        $html .= Former::select('app_user_select')->options($users)
            ->placeholder('APP User')->label('')->required();
        $html .= '<div id="sip_users"></div>';

        return $html;
    }

    public static function getJsonParamId($parameters)
    {
        foreach ($parameters as $paramId => $value) {
            $param = DB::table('did_action_parameters')->find($paramId);
            if ($param and $param->name == 'Key-Action') {
                return $paramId;
            }
        }

        return false;
    }
}
