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

    protected $usageValidationRules = [
        'user_id' => 'required_without:app_id|exists:users,id,deleted_at,NULL',
        'app_id'  => 'required_without:user_id|exists:app,id,deleted_at,NULL',
        'start'   => 'required|date_format:Y-m-d',
        'end'     => 'required|date_format:Y-m-d'
    ];

    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/info/daily-usage",
     *     summary="Daily usage",
     *     tags={"information"},
     *     @SWG\Parameter(
     *         description="APP User ID (if app_id not provided)",
     *         name="user_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="APP ID (if user_id not provided)",
     *         name="app_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Start from",
     *         name="start",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Parameter(
     *         description="End to",
     *         name="end",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Response(response="200", description="Daily usage"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getDailyUsage()
    {
        $this->setValidator($this->usageValidationRules);

        $alias = $this->getAppOrUserAlias();

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

    /**
     * @SWG\Get(
     *     path="/api/info/detail-did-usage",
     *     summary="Detail DID usage",
     *     tags={"information"},
     *     @SWG\Parameter(
     *         description="APP User ID (if app_id not provided)",
     *         name="user_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="APP ID (if user_id not provided)",
     *         name="app_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Start from",
     *         name="start",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Parameter(
     *         description="End to",
     *         name="end",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Response(response="200", description="Detail DID usage"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getDetailDidUsage()
    {
        $this->setValidator($this->usageValidationRules);

        $alias = $this->getAppOrUserAlias();

        $resource = $this->getResourceByAliasFromBillingDB($alias);
        $didUsage = [];
        if ($resource) {
            $didUsage = $this->getDIDUsageFromBillingDB($resource->resource_id, '',
                $this->request->has('app_id'));
            $didUsage = $didUsage->whereBetween('report_time',
                [$this->request->start, $this->request->end])->get();
        }

        return $this->defaultResponse(['entities' => $didUsage]);

    }

    /**
     * @SWG\Get(
     *     path="/api/info/cdr",
     *     summary="CDR",
     *     tags={"information"},
     *     @SWG\Parameter(
     *         description="APP User ID (if app_id not provided)",
     *         name="user_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="APP ID (if user_id not provided)",
     *         name="app_id",
     *         in="query",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         description="Start from",
     *         name="start",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *     @SWG\Parameter(
     *         description="End to",
     *         name="end",
     *         in="query",
     *         required=true,
     *         type="string",
     *         format="date"
     *     ),
     *      @SWG\Parameter(
     *         description="ANI",
     *         name="ani",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         description="DNIS",
     *         name="dnis",
     *         in="query",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response="200", description="CDR"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getCdr()
    {
        $cdrRules = [
            'ani' => 'required|numeric',
            'dnis' => 'required|numeric'
        ];

        $rules = array_merge($cdrRules, $this->usageValidationRules);
        $this->setValidator($rules);

        $alias    = $this->getAppOrUserAlias();
        $resource = $this->getResourceByAliasFromBillingDB($alias);
        $cdr = [];
        if ($resource) {
            $cdr = $this->getDIDUsageFromBillingDB($resource->resource_id, '',
                $this->request->ani,
                $this->request->dnis,
                $this->request->has('app_id'))->get();
        }

        return $this->defaultResponse(['entities' => $cdr]);

    }

    protected function getAppOrUserAlias()
    {
        if ($this->request->has('app_id')) {
            $app   = App::find($this->request->app_id);
            $alias = $app->getAppAlias();
        } else {
            $user  = AppUser::find($this->request->user_id);
            $alias = $user->getUserAlias();
        }

        return $alias;
    }

}
