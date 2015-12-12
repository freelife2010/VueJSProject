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

    public static function getActionParameterHtml($parameter, $app)
    {
        $selectName = "parameters[$parameter->id]";

        switch ($parameter->name) {
            case 'APP user id':
                $users = AppUser::whereAppId($app->id)->lists('name', 'user_id');
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
            default:
                $html = Former::text($selectName)->required()
                        ->placeholder($parameter->name)->raw() . '<br/>';

        }

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
