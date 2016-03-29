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
    Route::get('/edit-profile/{id}', 'UserController@getEditProfile');
    Route::post('/users/edit/{id}', 'UserController@postEdit');
    Route::controller('home', 'HomeController');
    Route::controller('app', 'AppController');
    Route::controller('did', 'DIDController');
    Route::controller('payments', 'PaymentController');
    Route::controller('app-users', 'AppUsersController');
    Route::controller('app-keys', 'AppKeysController');
    Route::controller('app-rates', 'AppRateController');
    Route::controller('app-cdr', 'AppCDRController');
    Route::controller('cdr', 'CDRController');
    Route::controller('conferences', 'ConferenceController');
    Route::controller('queues', 'QueueController');
    Route::controller('pbx', 'PBXController');
    Route::controller('sms', 'SMSController');
    Route::controller('conferences', 'ConferenceController');
});

Route::get('/resendEmail', 'Auth\AuthController@resendEmail');

Route::get('/activate/{code}', 'Auth\AuthController@activateAccount');

//Admin routes
Route::group(['middleware' => ['admin', 'csrf']], function() {
    Route::controller('emails', 'EmailController');
    Route::controller('costs', 'CostController');
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

    //SIP user routes
    $api->get('sip-password', 'App\API\Controllers\UserController@getSipPassword');
    $api->get('sipuser/list', 'App\API\Controllers\UserController@getSipUserList');
    $api->post('sipuser/add', 'App\API\Controllers\UserController@postSipUserAdd');
    $api->post('sipuser/delete', 'App\API\Controllers\UserController@postSipUserDelete');

    //Controllers
    $api->controller('did', 'App\API\Controllers\DIDController');
    $api->controller('sms', 'App\API\Controllers\SMSAPIController');
    $api->controller('conference', 'App\API\Controllers\ConferenceAPIController');
    $api->controller('queue', 'App\API\Controllers\QueueAPIController');
    $api->controller('info', 'App\API\Controllers\InfoAPIController');
    $api->controller('ivr', 'App\API\Controllers\IVRAPIController');

    //File api routes
    $api->get('voicemail/list/{id}', 'App\API\Controllers\FileAPIController@getVoicemailList');
    $api->get('voicemail/file/{id}', 'App\API\Controllers\FileAPIController@getVoicemailFile');

    //freeSwitch routes
    $api->get('fs/get_call_handler', 'App\API\Controllers\FreeswitchController@getCallHandler');
    $api->get('fs/join_conference', 'App\API\Controllers\FreeswitchController@getJoinConference');
    $api->get('fs/leave_conference', 'App\API\Controllers\FreeswitchController@getLeaveConference');
    $api->get('fs/agent_join_queue', 'App\API\Controllers\FreeswitchController@getAgentQueueJoin');
    $api->get('fs/agent_leave_queue', 'App\API\Controllers\FreeswitchController@getAgentQueueLeave');
    $api->get('fs/caller_join_queue', 'App\API\Controllers\FreeswitchController@getCallerQueueJoin');
    $api->get('fs/caller_leave_queue', 'App\API\Controllers\FreeswitchController@getCallerQueueLeave');

    //payment controller
    $api->get('balance', 'App\API\Controllers\PaymentAPIController@getBalance');
    $api->post('addCredit', 'App\API\Controllers\PaymentAPIController@postAddCredit');
    $api->get('creditHistory', 'App\API\Controllers\PaymentAPIController@getCreditHistory');
    $api->get('getAllowedCountry', 'App\API\Controllers\PaymentAPIController@getAllowedCountry');
    $api->get('getRates', 'App\API\Controllers\PaymentAPIController@getRates');
    $api->get('getRate', 'App\API\Controllers\PaymentAPIController@getRate');
});

//Grants access token
Route::post('api/token', function() {
    return Response::json(Authorizer::issueAccessToken());
});

//Freeswitch XML response method
Route::post('dialplan', '\App\API\Controllers\FreeswitchController@getFreeswitchResponse');
Route::post('user', '\App\API\Controllers\FreeswitchController@getFreeswitchUser');


//test xml conversion method
Route::post('testxml', function() {
    $request = \Illuminate\Http\Request::capture();
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><document></document>');
    $xml->addAttribute('type', 'freeswitch/xml');
    $section = $xml->addChild('section');
    $section->addAttribute('name', 'dialplan');
    $section->addAttribute('description', 'dialplan');
    $context = $section->addChild('context');
    $context->addAttribute('name', 'default');
    $extension = $context->addChild('extension');
    $extension->addAttribute('name', 'test9');
    $condition = $extension->addChild('condition');
    $condition->addAttribute('field', 'destination_number');
    $condition->addAttribute('expression', '^(.*)$');
    parseXml($request->xml, $condition);

    return new \Dingo\Api\Http\Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
});

function parseXml($simpleXml, $condition)
{
    $simpleXml = new SimpleXMLElement($simpleXml);
    $action    = $condition->addChild('action');
    appendAttributes($simpleXml->attributes(), $action);
    appendChildren($simpleXml, $action);
}

function appendChildren($element, $parent)
{
    $children = $element->children();

    foreach ($children as $child) {
        $appendedChild = $parent->addChild($child->getName(), (string) $child);
        if ($child->attributes())
            appendAttributes($child->attributes(), $appendedChild);
        if ($child->children())
            appendChildren($child, $appendedChild);
    }
}

function appendAttributes($attributes, $parent)
{
    $attr = $parent->attributes();
    foreach ($attributes as $attribute) {
        if (!isset($attr[$attribute->getName()]))
        $parent->addAttribute($attribute->getName(), (string) $attribute);
    }
}