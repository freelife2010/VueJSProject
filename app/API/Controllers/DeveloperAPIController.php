<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Models\App;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeveloperAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/developer/balance",
     *     summary="Return developer's balance",
     *     tags={"developer"},
     *     @SWG\Response(response="200", description="Balance"),
     *     @SWG\Response(response="400", description="Bad request"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getBalance()
    {
        $developer = $this->getDeveloper();

        return $this->defaultResponse(['balance' => $developer->getClientBalance()]);
    }

    /**
     * @SWG\Get(
     *     path="/api/developer/app-list",
     *     summary="Return App list of current developer",
     *     tags={"developer"},
     *     @SWG\Response(response="200", description="App list"),
     *     @SWG\Response(response="400", description="Bad request"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getAppList()
    {
        $developer = $this->getDeveloper();

        return $this->defaultResponse(['apps' => $developer->apps->pluck('name')]);
    }

    /**
     * @SWG\Get(
     *     path="/api/developer/app-status",
     *     summary="Return current app's status'",
     *     tags={"developer"},
     *     @SWG\Response(response="200", description="App status"),
     *     @SWG\Response(response="400", description="Bad request"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getAppStatus()
    {
        $appId     = $this->getAPPIdByAuthHeader();
        $app       = App::findOrFail($appId);
        $appStatus = $this->makeAppStatus($app);

        return $this->defaultResponse(['app_status' => $appStatus]);
    }

    /**
     * @SWG\Post(
     *     path="/api/developer/change-password",
     *     summary="Create new APP",
     *     tags={"developer"},
     *     @SWG\Parameter(
     *         description="New password",
     *         in="formData",
     *         name="password",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function postChangePassword()
    {
        $developer = $this->getDeveloper();

        $developer->password = $this->request->password;

        if ($developer->save())
            return $this->defaultResponse(['result' => 'Password changed']);
        else $this->response->errorInternal('Could not change password');
    }

    private function getDeveloper()
    {
        $appId = $this->getAPPIdByAuthHeader();
        $app   = App::findOrFail($appId);

        if (!$app->developer)
            throw new NotFoundHttpException('Developer not found');

        return $app->developer;
    }

    private function makeAppStatus($app)
    {
        return [
            'status'       => $app->status ? 'Active' : 'Inactive',
            'users'        => $app->users->pluck('email'),
            'active_users' => $app->users()->where('users.last_status', 1)->get()->pluck('email')
        ];
    }

    /**
     * @SWG\Get(
     *     path="/api/developer/cdr",
     *     summary="CDR",
     *     tags={"developer"},
     *     @SWG\Parameter(
     *         description="Filter",
     *         in="query",
     *         name="filter",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="App Id",
     *         in="query",
     *         name="app_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Client Id",
     *         in="query",
     *         name="client_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Date From",
     *         in="query",
     *         name="date_from",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="Date To",
     *         in="query",
     *         name="date_to",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Success result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     */
    public function getCdr()
    {
        $validationRules = [
//            'app_id'  => 'exists:app,id,deleted_at,NULL',
            'date_from'   => 'required|date_format:Y-m-d',
            'date_to'     => 'required|date_format:Y-m-d'
        ];
        $this->setValidator($validationRules);

        $validFilterValues = ['Peer To Peer', 'DID Calls', 'Toll Free Calls', 'Forwarded Calls', 'Dialed Calls', 'Mass Call'];
        if (!in_array($this->request->filter, $validFilterValues)) {
            $this->response->errorInternal('Filter was not found. Valid Filter values: ' . implode(', ', $validFilterValues));
        }

        $dailyTableName = 'client_cdr' . date('Ymd');//, strtotime('-1 day'));
        $fields = [
            'time',
            'trunk_id_origination',
            'alias',
            'origination_source_number',
            'routing_digits',
            'call_duration',
            'egress_rate',
            'egress_cost'
        ];

        $query = $this->getFluentBilling($dailyTableName)
            ->select($fields)
            ->leftJoin('resource', 'ingress_client_id', '=', 'resource_id');

        switch ($this->request->filter) {
            case 'Peer To Peer':
                break;
            case 'DID Calls':
                if (empty($this->request->client_id) || !(int) $this->request->client_id) {
                    $this->response->errorInternal('Client Id is required for this filter');
                }
                $query->where('ingress_client_id', '=', $this->request->client_id);
                break;
            case 'Toll Free Calls':
                $clientId = getClientIdByAliasFromBillingDB('Opentact_TF_Term');
                $query->where('egress_client_id', '=', $clientId);
                break;
            case 'Forwarded Calls':
                if (empty($this->request->app_id)) {
                    $this->response->errorInternal('App Id is required for this filter');
                }
                $query->where('origination_source_host_name', '=', '108.165.2.110');
                if ((int) $this->request->app_id) {
                    $alias = App::find($this->request->app_id)->getAppAlias();
                    $query->where('trunk_id_termination', '=', $alias);
                }

                break;
            case 'Dialed Calls':
                if (empty($this->request->app_id)) {
                    $this->response->errorInternal('App Id is required for this filter');
                }
                $query->where('origination_source_host_name', '!=', '108.165.2.110');
                if ((int) $this->request->app_id) {
                    $alias = App::find($this->request->app_id)->getAppAlias();
                    $query->where('trunk_id_termination', '=', $alias);
                }
                break;
            case 'Mass Call':
                if (empty($this->request->app_id)) {
                    $this->response->errorInternal('App Id is required for this filter');
                }
                if ((int) $this->request->app_id) {
                    $alias = App::find($this->request->app_id)->getAppAlias();
                    $query->where('trunk_id_termination', '=', $alias . '_CC_term');
                }
                break;
            default:
                break;
        }
        return $this->defaultResponse(['result' => $query->get()]);
    }
}
