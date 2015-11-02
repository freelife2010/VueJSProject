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


Route::pattern('id', '[0-9]+');

Route::controllers([
    'auth' => 'Auth\AuthController',
    'password' => 'Auth\PasswordController'
]);


Route::group(['middleware' => ['auth', 'csrf']], function() {
    Route::get('/', 'HomeController@getIndex');
    Route::controller('home', 'HomeController');
    Route::controller('emails', 'EmailController');
    Route::controller('app', 'AppController');
    Route::controller('app-users', 'AppUsersController');
    Route::controller('app-cdr', 'AppCDRController');
});

Route::get('/resendEmail', 'Auth\AuthController@resendEmail');

Route::get('/activate/{code}', 'Auth\AuthController@activateAccount');

Route::group(['middleware' => 'admin'], function() {
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
});


//API Routes

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['middleware' => 'api.auth'], function ($api) {
    $api->resource('users', 'App\API\Controllers\UserController');
});