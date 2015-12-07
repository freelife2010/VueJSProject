<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


use LucaDegasperi\OAuth2Server\Facades\Authorizer;

Response::macro('xml', function(array $vars, $status = 200, array $header = [], $xml = null)
{
    if (is_null($xml)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
    }
    foreach ($vars as $key => $value) {
        if (is_array($value)) {
            Response::xml($value, $status, $header, $xml->addChild($key));
        } else {
            $xml->addChild($key, $value);
        }
    }
    if (empty($header)) {
        $header['Content-Type'] = 'application/xml';
    }
    return Response::make($xml->asXML(), $status, $header);
});

Route::pattern('id', '[0-9]+');

Route::controllers([
    'auth' => 'Auth\AuthController',
    'password' => 'Auth\PasswordController'
]);


Route::group(['middleware' => ['auth', 'csrf']], function() {
    Route::get('/', 'HomeController@getIndex');
    Route::controller('home', 'HomeController');
    Route::controller('app', 'AppController');
    Route::controller('did', 'DIDController');
    Route::controller('payments', 'PaymentController');
    Route::controller('app-users', 'AppUsersController');
    Route::controller('app-keys', 'AppKeysController');
    Route::controller('cdr', 'CDRController');
    Route::controller('app-cdr', 'AppCDRController');
    Route::controller('conferences', 'ConferenceController');
    Route::controller('queues', 'QueueSessionController');
    Route::controller('sms', 'SMSController');
});

Route::get('/resendEmail', 'Auth\AuthController@resendEmail');

Route::get('/activate/{code}', 'Auth\AuthController@activateAccount');

Route::group(['middleware' => ['admin', 'csrf']], function() {
    Route::controller('emails', 'EmailController');
    Route::controller('revisions', 'RevisionsController');
    Route::controller('users', 'UserController');
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
});


//API Routes

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->post('apps', 'App\API\Controllers\PublicAPIController@CreateAPP');
});

$api->version('v1', ['middleware' => 'api.auth'], function ($api) {

    $api->get('token', 'App\API\Controllers\PublicAPIController@getTokenInfo');

    $api->get('users', 'App\API\Controllers\UserController@getUsers');
    $api->post('users', 'App\API\Controllers\UserController@createUsers');
    $api->get('users/{username}', 'App\API\Controllers\UserController@getUserInfo');

    $api->controller('did', 'App\API\Controllers\DIDController');

    //freeSwitch routes
    $api->get('fs/get_call_handler', 'App\API\Controllers\FreeswitchController@getCallHandler');
    $api->get('fs/join_conference', 'App\API\Controllers\FreeswitchController@getJoinConference');
    $api->get('fs/leave_conference', 'App\API\Controllers\FreeswitchController@getLeaveConference');
    $api->get('fs/agent_join_queue', 'App\API\Controllers\FreeswitchController@getAgentQueueJoin');
    $api->get('fs/agent_leave_queue', 'App\API\Controllers\FreeswitchController@getAgentQueueLeave');
    $api->get('fs/caller_join_queue', 'App\API\Controllers\FreeswitchController@getCallerQueueJoin');
    $api->get('fs/caller_leave_queue', 'App\API\Controllers\FreeswitchController@getCallerQueueLeave');
});

//Grants access token
Route::post('api/token', function() {
    return Response::json(Authorizer::issueAccessToken());
});

//Freeswitch XML response method
Route::post('dialplan', '\App\API\Controllers\FreeswitchController@getFreeswitchResponse');
