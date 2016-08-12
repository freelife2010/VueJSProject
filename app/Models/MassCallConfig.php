<?php

namespace App\Models;

use App\Helpers\BillingTrait;
use App\Helpers\Misc;
use Auth;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Venturecraft\Revisionable\RevisionableTrait;

class MassCallConfig extends BaseModel
{
    use RevisionableTrait, BillingTrait;

    protected $primaryKey = 'id';
    protected $table = 'mass_call_config';
    public $timestamps = true;

    protected $fillable = [
        'app_id',
        'enabled'
    ];
    protected $dates = [
        'created_at'
    ];
}
