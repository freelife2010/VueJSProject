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

class GoogleApiData extends BaseModel
{
    use RevisionableTrait, BillingTrait;

    protected $primaryKey = 'id';
    protected $table = 'google_api_data';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'project_id',
        'private_key_id',
        'private_key',
        'client_email',
        'client_id',
        'client_x509_cert_url'
    ];
    protected $dates = [
        'created_at'
    ];
}
