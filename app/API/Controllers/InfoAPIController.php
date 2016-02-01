<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Models\App;
use App\Models\AppUser;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class InfoAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    public function __construct()
    {
        $this->initAPI();
    }

    public function getDailyUsage()
    {
        $this->setValidator([
            'user_id' => 'required_without:app_id|exists:users,id,deleted_at,NULL',
            'app_id'  => 'required_without:user_id|exists:app,id,deleted_at,NULL',
            'start'   => 'required|date_format:Y-m-d',
            'end'     => 'required|date_format:Y-m-d'
        ]);

        if ($this->request->has('app_id')) {
            $app   = App::find($this->request->app_id);
            $alias = $app->alias;
        } else {
            $user  = AppUser::find($this->request->user_id);
            $alias = $user->getUserAlias();
        }

        $resource = $this->getResourceByAliasFromBillingDB($alias);
        $dailyUsage = [];
        if ($resource) {
            $dailyUsage = $this->getDailyUsageFromBillingDB($resource->resource_id, '',
                                $this->request->has('app_id'));
            $dailyUsage = $dailyUsage->whereBetween('report_time',
                [$this->request->start, $this->request->end])->get();
        }

        return $this->defaultResponse(['entities' => $dailyUsage]);
    }
}
