<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Models\App;
use Dingo\Api\Routing\Helpers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeveloperAPIController extends Controller
{
    use Helpers, APIHelperTrait;

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
     *     summary="Return App's status'",
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
            'status' => $app->status ? 'Active' : 'Inactive',
            'users' => $app->users->pluck('email'),
            'active_users' => $app->users()->where('users.last_status', 1)->get()->pluck('email')
        ];
    }
}
