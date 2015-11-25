<?php

namespace App\Models;


class DidActionParameters extends BaseModel
{
    protected $table = 'did_actions_parameters';

    public function scopeJoinParamTable($query) {
        return $query->join('did_action_parameters', 'parameter_id', '=', 'did_action_parameters.id');
    }
}
