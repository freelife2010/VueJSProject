<?php

namespace App\Models;

use App\API\ApiClient\GuzzleClient;
use App\Helpers\BillingTrait;
use Auth;
use Former\Facades\Former;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DID extends BaseModel
{
    use GuzzleClient, SoftDeletes, BillingTrait;

    const ACTION_TTS_ID = 9;

    protected $table = 'did';

    protected $fillable = [
        'did',
        'app_id',
        'account_id',
        'action_id',
        'owned_by',
        'rate_center',
        'reserve_id',
        'did_type',
        'state',
        'npa',
        'nxx',
        'city'
    ];

    protected $credentials = [
        'accountno' => '',
        'token' => ''
    ];

    protected function app()
    {
        return $this->belongsTo('App\Models\App', 'app_id');
    }

    protected function appUser()
    {
        return $this->belongsTo('App\Models\AppUser', 'owned_by');
    }

    protected function developer()
    {
        return $this->belongsTo('App\User', 'account_id');
    }

    public function actionParameters() {
        return $this->hasMany('App\Models\DIDActionParameters', 'did_id');
    }

    public function scopeAction($query) {
        return $query->join('did_action', 'action_id', '=', 'did_action.id');
    }

    function __construct()
    {
        $config = [
            'base_uri' => 'https://customer.vitcom.net/api/did/',
            'timeout'  => 15
        ];
        $this->createHttpClient($config);
        $this->setCredentials();
    }

    protected function setCredentials()
    {
        $this->credentials['accountno'] = env('DID_API_ID', 2334425286);
        $this->credentials['token'] = env('DID_API_TOKEN', 'o088c565712a945f5b45fe26bdab5d72');
    }

    public function getStates()
    {
        $data = $this->makeData();
        $response = $this->sendPost('availabilitystate', $data);

        return $this->makeResponse($response);
    }

    public function getNPA($state)
    {
        $data = $this->makeData(['state' => $state]);
        $response = $this->sendPost('availabilitynpanxx', $data);

        return $this->makeResponse($response);
    }

    public function getAvailableNumbers($state, $rateCenter = '')
    {
        $data = $this->makeData(['state' => $state, 'ratecenter' => $rateCenter]);
        $response = $this->sendPost('searchdid', $data);

        return $this->makeResponse($response);
    }

    public function reserveDID($did)
    {
        $category = 'Landline';
        $data     = $this->makeData(['did' => $did, 'category' => $category]);
        $response = $this->sendPost('reserve', $data);

        return $this->makeResponse($response);
    }

    public function buyDID($did)
    {
        $category = 'Landline';
        $data     = $this->makeData([
            'number'   => $did,
            'category' => $category,
            'trunk_id' => 990259
        ]);

        $response = $this->sendPost('generate_new_order', $data);

        return $this->makeResponse($response);
    }

    protected function makeData($params = [])
    {
        $params = array_merge($this->credentials, $params);

        $data = [
            'form_params' => $params
        ];

        return $data;
    }

    protected function makeResponse($response, $dataField = 'data')
    {
        $code     = $response->getStatusCode();
        $response = json_decode($response->getBody());

        return (isset($response->error)
            or !isset($response->$dataField)
            or $code != 200) ? false :
                $dataField ?
                    $response->$dataField :
                    $response;
    }

    public function getList($data, $labelField, $allOption = true)
    {
        $list = $allOption ? ['All'] : [];
        foreach ($data as $index => $entry) {
            $list[$entry->$labelField] = $entry->$labelField;
        }

        return $list;
    }

    public function findReservedDID($number, $storedDIDs)
    {
        foreach ($storedDIDs as $did) {
            if ($did->TN == $number)
                return $did;
        }

        return false;
    }

    public function fillParams($request, $reserveId)
    {
        $params               = $request->all();
        $user                 = Auth::user();
        $params['reserve_id'] = $reserveId;
        $params['account_id'] = $user->id;
        $params['action_id']  = $params['action'];
        if (!empty($params['outside_number']))
            $params['did'] = $params['outside_number'];
        $storedDIDs           = $request->session()->get('dids');
        $storedDID            = $this->findReservedDID($request->did, $storedDIDs);
        if ($storedDID) {
            $params['did_type']    = $storedDID->category;
            $params['rate_center'] = $storedDID->RateCenter;
        }
        $this->fill($params);
    }

    public function createDIDParameters($request)
    {
        $parameters = $request->parameters;

        if ($parameters)
            foreach ($parameters as $paramId => $value) {
                if ($request->action == self::ACTION_TTS_ID)
                    $value = $this->makePlaybackTTS($value);
                $didParameter                  = new DIDActionParameters();
                $didParameter->did_id          = $this->id;
                $didParameter->action_id       = $request->action;
                $didParameter->parameter_id    = $paramId;
                $didParameter->parameter_value = $value;
                $didParameter->save();
            }

        return true;
    }

    public function deleteDIDParameters()
    {
        $didParams = $this->actionParameters;
        foreach ($didParams as $parameter) {
            if (!$parameter->delete()) return false;
        }

        return true;
    }

    public function createBillingDBData()
    {
        $appUser        = AppUser::find($this->owned_by);
        $resource       = $this->getResourceByAliasFromBillingDB($appUser->getUserAlias());
        $itemId         = $this->insertGetIdToBillingDB('
                        insert into product_items
                        (product_id, digits, strategy, min_len, max_len, update_at)
                        values (1,?,1,0,32, ?) RETURNING item_id',
            [$this->did, date('Y-m-d H:i:s')], 'item_id');

        $appDidResource = $this->getResourceByAliasFromBillingDB($appUser->app->alias);
        $this->getFluentBilling('resource_prefix')->insert([
            'resource_id'       => $appDidResource ? $appDidResource->resource_id : 0,
            'route_strategy_id' => 138,
            'rate_table_id'     => 2262,
            'code'              => $this->did,
            'product_id'        => 0
        ]);
        if ($itemId and $resource) {
            $values = [
                'item_id'     => $itemId,
                'resource_id' => $resource->resource_id
            ];
            $this->getFluentBilling('product_items_resource')->insert($values);
        }

    }

    public function makePlaybackTTS($string)
    {
        $playbackHost = 'http://198.245.49.222';
        $filename     = Str::random(10)."_$this->id.wav";
        $workDir      = storage_path('app/voice');

        $data = addslashes(json_encode([
            'speaker_name' => 'Allison',
            'text'         => $string
        ]));

        $curlCmd = 'curl -H "Content-Type: application/json" -X POST -d';
        $cmd = "$curlCmd \"$data\" $playbackHost > $filename";

        $process = new Process($cmd, $workDir);
        $process->run();
        \Log::alert('Trying to do TTS', ['cmd' => $cmd]);

        if (!$process->isSuccessful())
            \Log::error('Error while using TTS', [
                'stdErr' => $process->getErrorOutput(),
                'stdOut' => $process->getOutput()
            ]);

        return $filename;
    }

}
