<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/* @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['middleware' => ['auth', 'version-switcher']], function () use ($router) {
    $router->get('/list/{type}', 'ListController@get');
    $router->head('/list/{type}/{item}', 'ListController@itemAvailability');
    $router->put('/list/{type}/{item}', 'ListController@addItem');
    $router->delete('/list/{type}/{item}', 'ListController@removeItem');

    $router->put('/migrate/{openlistId}', 'MigrateController@migrate');
});
