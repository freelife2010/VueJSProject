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

Response::macro('xml', function (array $vars, $status = 200, array $header = [], $xml = null) {
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
    'auth'     => 'Auth\AuthController',
    'password' => 'Auth\PasswordController'
]);

Route::get('/', 'HomeController@getIndex');
Route::get('/voice/{filename}', 'HomeController@getVoiceMail');
Route::controller('app-config', 'AppConfigController');
Route::controller('usage-history', 'UsageHistoryController');
Route::controller('credit-history', 'AppCreditHistoryController');
Route::controller('app', 'AppController');
Route::controller('did', 'DIDController');
Route::controller('did-admin', 'DIDAdminController');

Route::group(['middleware' => ['auth', 'csrf', 'role:developer']], function () {
    Route::controller('home', 'HomeController');
    Route::controller('app-users', 'AppUsersController');
    Route::controller('app-invoice', 'AppInvoiceController');
    Route::controller('app-keys', 'AppKeysController');
    Route::controller('app-rates', 'AppRateController');
    Route::controller('app-mass-rates', 'AppMassRateController');
    Route::controller('app-cdr', 'AppCDRController');
    Route::controller('cdr', 'CDRController');
    Route::controller('conferences', 'ConferenceController');
    Route::controller('queues', 'QueueController');
    Route::controller('pbx', 'PBXController');
    Route::controller('sms', 'SMSController');
    Route::controller('conferences', 'ConferenceController');

    Route::group(['prefix' => 'payments'], function () {
        Route::get('/', 'PaymentController@getIndex');
        Route::get('data', 'PaymentController@getData');
        Route::get('add-credit', 'PaymentController@getAddCredit');
        Route::get('paypal-status', 'PaymentController@getPaypalStatus');
        Route::post('create-stripe', 'PaymentController@postCreateStripe');
        Route::post('create-paypal', 'PaymentController@postCreatePaypal');
    });

    Route::group(['prefix' => 'app-user-payments'], function () {
        Route::get('/{id}', 'AppUserPaymentController@getIndex')->name('user_payments.index');
        Route::get('/data/{id}', 'AppUserPaymentController@getData')->name('user_payments.data');
        Route::get('/add-credit/{id}', 'AppUserPaymentController@getAddCredit')->name('user_payments.add_credit');
        Route::post('create-stripe/{id}', 'AppUserPaymentController@postCreateStripe')->name('user_payments.create_stripe');
        Route::post('create-paypal/{id}', 'AppUserPaymentController@postCreatePaypal')->name('user_payments.create_paypal');
        Route::get('/paypal-status/{id}', 'AppUserPaymentController@getPaypalStatus')->name('user_payments.paypal_status');
    });
});

Route::group(['middleware' => ['auth', 'csrf']], function () {
    Route::get('/edit-profile/{id}', 'UserController@getEditProfile');
    Route::post('/users/edit/{id}', 'UserController@postEdit');
});

Route::get('/resendEmail', 'Auth\AuthController@resendEmail');

Route::get('/activate/{code}', 'Auth\AuthController@activateAccount');

//Admin routes
Route::group(['middleware' => ['auth', 'admin', 'csrf']], function () {
    Route::controller('emails', 'EmailController');
    Route::controller('costs', 'CostController');
    Route::controller('revisions', 'RevisionsController');
    Route::controller('users', 'UserController');
    Route::controller('rates', 'RateController');

    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

    Route::get('payments/admin', 'PaymentController@getAdmin');
    Route::get('payments/admin-data', 'PaymentController@getAdminData');
});


//API Routes

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->post('app/create', 'App\API\Controllers\PublicAPIController@CreateAPP');

    //Grants access token
    $api->post('token', function () {
        return Response::json(Authorizer::issueAccessToken());
    });
});

$api->version('v1', ['middleware' => 'api.auth'], function ($api) {

    $api->get('token', 'App\API\Controllers\PublicAPIController@getTokenInfo');

    $api->get('users', 'App\API\Controllers\UserController@getUsers');
    $api->post('users', 'App\API\Controllers\UserController@createUsers');
    $api->post('users/change-password', 'App\API\Controllers\UserController@changePassword');
    $api->get('users/{username}', 'App\API\Controllers\UserController@getUserInfo');

    //SIP user routes
    $api->get('sip-password', 'App\API\Controllers\UserController@getSipPassword');
    $api->get('sipuser/list', 'App\API\Controllers\UserController@getSipUserList');
    $api->post('sipuser/add', 'App\API\Controllers\UserController@postSipUserAdd');
    $api->post('sipuser/delete', 'App\API\Controllers\UserController@postSipUserDelete');

    //Controllers
    $api->controller('did', 'App\API\Controllers\DIDController');
    $api->post('did/searchUSTFdid', 'App\API\Controllers\DIDController@postSearchUSTFdid');
    $api->controller('sms', 'App\API\Controllers\SMSAPIController');
    $api->controller('conference', 'App\API\Controllers\ConferenceAPIController');
    $api->controller('queue', 'App\API\Controllers\QueueAPIController');
    $api->controller('info', 'App\API\Controllers\InfoAPIController');
    $api->controller('ivr', 'App\API\Controllers\IVRAPIController');
    $api->controller('developer', 'App\API\Controllers\DeveloperAPIController');
    $api->controller('friend', 'App\API\Controllers\FriendAPIController');
    $api->controller('mass-call', 'App\API\Controllers\MassCallAPIController');
    $api->controller('rates', 'App\API\Controllers\RateController');

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


//Freeswitch XML response method
Route::post('dialplan', '\App\API\Controllers\FreeswitchController@getFreeswitchResponse');
Route::post('user', '\App\API\Controllers\FreeswitchController@getFreeswitchUser');


//test xml conversion method
Route::post('testxml', function () {
    $request = \Illuminate\Http\Request::capture();

    return \App\Helpers\Misc::testXml($request);
});
