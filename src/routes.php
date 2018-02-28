<?php
use \Illuminate\Support\Facades\Route;


Route::group(['prefix'=>config('passport-proxy.route_prefix'),'middleware'=>config('passport-proxy.middleware'),'namespace'=>'Montross50\PassportProxy\Http\Controllers'],function() {
    Route::post('/login', 'ProxyLoginController@login');
    Route::get('/login/scopes', 'ProxyLoginController@getScopes');
    Route::post('/login/refresh', 'ProxyLoginController@refresh');

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('/login/token-scopes', 'ProxyLoginController@currentScope');
        Route::post('/logout', 'ProxyLoginController@logout');

    });
});
