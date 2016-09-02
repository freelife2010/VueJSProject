<?php

namespace App\API\Controllers;

use App\API\APIHelperTrait;
use App\Helpers\BillingTrait;
use App\Models\App;
use App\Models\IVR;
use App\User;
use Auth;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ImAPIController extends Controller
{
    use Helpers, APIHelperTrait, BillingTrait;

    protected $apiUrl = 'http://104.131.190.229:5555/v1/api/';
    protected $token = '123456789';
    protected $password = '123456789';
    protected $userPassword = '123456';

    public function __construct()
    {
        $this->initAPI();
    }

    /**
     * @SWG\Get(
     *     path="/api/im/list",
     *     summary="Get List",
     *     tags={"im"},
     *     @SWG\Response(response="200", description="Get List"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getList()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->apiUrl . 'domain/names');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        $out = curl_exec($curl);
        $result = json_decode(curl_close($curl));
        return $this->defaultResponse(['entities' => [$result]]);
    }

    /**
     * @SWG\Get(
     *     path="/api/im/user-list",
     *     summary="Get User List",
     *     tags={"im"},
     *     @SWG\Parameter(
     *         description="Domain Name",
     *         in="formData",
     *         name="domain_name",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Get User List"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function getUserList()
    {
        $domain = $this->request->domain_name;
        if (empty($domain)) {
            $appId = $this->getAPPIdByAuthHeader();
            $domain = App::find($appId)->tech_prefix;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->apiUrl . 'users/host' . $domain);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        $out = curl_exec($curl);
        $result = json_decode(curl_close($curl));
        return $this->defaultResponse(['entities' => [$result]]);
    }

    /**
     * @SWG\Put(
     *     path="/api/im/name",
     *     summary="Put Name",
     *     tags={"im"},
     *     @SWG\Parameter(
     *         description="Domain Name",
     *         in="formData",
     *         name="domain_name",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function putName()
    {
        $domain = $this->request->domain_name;
        if (empty($domain)) {
            $appId = $this->getAPPIdByAuthHeader();
            $domain = App::find($appId)->tech_prefix;
        }

        $curl = curl_init($this->apiUrl . 'domain/name/' . $domain);
        $data = ['password' => $this->password];
        $dataJson = json_encode($data);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Cache-Control: no-cache', "Postman-Token: $this->token"]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
        $response = curl_exec($curl);

        return $this->defaultResponse(['success' => [true]]);
    }

    /**
     * @SWG\Put(
     *     path="/api/im/add-name",
     *     summary="Add Name",
     *     tags={"im"},
     *     @SWG\Parameter(
     *         description="Domain Name",
     *         in="formData",
     *         name="domain_name",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="User Name",
     *         in="formData",
     *         name="user_name",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function putAddUser()
    {
        $domain = $this->request->domain_name;
        if (empty($domain)) {
            $appId = $this->getAPPIdByAuthHeader();
            $domain = App::find($appId)->tech_prefix;
        }

        $curl = curl_init($this->apiUrl . 'users/host/' . $domain . '/username/' . $this->request->user_name);
        $data = ['password' => $this->userPassword];
        $dataJson = json_encode($data);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Cache-Control: no-cache', "Postman-Token: $this->token"]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
        $response = curl_exec($curl);

        return $this->defaultResponse(['success' => [true]]);
    }

    /**
     * @SWG\Delete(
     *     path="/api/im/name",
     *     summary="Delete Name",
     *     tags={"im"},
     *      @SWG\Parameter(
     *         description="Domain Name",
     *         in="formData",
     *         name="domain_name",
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function deleteName()
    {
        $domain = $this->request->domain_name;
        if (empty($domain)) {
            $appId = $this->getAPPIdByAuthHeader();
            $domain = App::find($appId)->tech_prefix;
        }

        $curl = curl_init($this->apiUrl . 'domain/name/' . $domain);
        $data = ['password' => $this->password];
        $dataJson = json_encode($data);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Cache-Control: no-cache', "Postman-Token: $this->token"]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
        $response = curl_exec($curl);

        return $this->defaultResponse(['success' => [true]]);
    }

    /**
     * @SWG\Delete(
     *     path="/api/im/user-name",
     *     summary="Delete Name",
     *     tags={"im"},
     *      @SWG\Parameter(
     *         description="Domain Name",
     *         in="formData",
     *         name="domain_name",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         description="User Name",
     *         in="formData",
     *         name="user_name",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(response="200", description="Result"),
     *     @SWG\Response(response="401", description="Auth required"),
     *     @SWG\Response(response="500", description="Internal server error")
     * )
     * @return bool|mixed
     */
    public function deleteUserName()
    {
        $domain = $this->request->domain_name;
        if (empty($domain)) {
            $appId = $this->getAPPIdByAuthHeader();
            $domain = App::find($appId)->tech_prefix;
        }

        $curl = curl_init($this->apiUrl . 'users/host/' . $domain . '/username/' . $this->request->user_name);
        $data = ['password' => $this->userPassword];
        $dataJson = json_encode($data);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Cache-Control: no-cache', "Postman-Token: $this->token"]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
        $response = curl_exec($curl);

        return $this->defaultResponse(['success' => [true]]);
    }
}
