<?php
use \Illuminate\Support\Facades\Route;

Route::group(['prefix'=>config('passport-consumer.route_prefix'),'middleware'=>config('passport-consumer.middleware'),'namespace'=>'Montross50\PassportConsumer\Http\Controllers'], function () {

    if (config('passport-consumer.enable_pg')) {
        $routeName = config('passport-consumer.route_name_pg');
        Route::post($routeName.'/login', 'PassportConsumerController@login');
        Route::post($routeName.'/refresh', [
            'as' => 'pg_refresh',
            'uses'=>'PassportConsumerController@refresh']);

        Route::group(['middleware' => ['auth:api']], function () use ($routeName) {
            Route::post($routeName. '/logout', 'PassportConsumerController@logout');
        });
    }

    if (config('passport-consumer.enable_access')) {
        $routeName = config('passport-consumer.route_name_access');
        Route::get($routeName.'/redirect', 'PassportConsumerController@redirect');
        Route::get($routeName.'/callback', 'PassportConsumerController@callback');
        Route::post($routeName.'/refresh', [
            'as' => 'access_refresh',
            'uses'=>'PassportConsumerController@refresh']);

        Route::group(['middleware' => ['auth:api']], function () use ($routeName) {
            Route::post($routeName. '/logout', 'PassportConsumerController@logout');
        });
    }
});
