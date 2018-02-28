<?php

return [
    'middleware' => [],
    'passport_secret_pg' => env('PC_PASSPORT_SECRET_PG'),
    'passport_secret_access' => env('PC_PASSPORT_SECRET_ACCESS'),
    'passport_id_pg' => env('PC_PASSPORT_ID_PG'),
    'passport_id_access' => env('PC_PASSPORT_ID_ACCESS'),
    'auth_provider_key' => 'auth.providers.users.model',
    'route_prefix' => 'identity',
    'route_name_pg' => 'grant',
    'route_name_access' => 'access',
    'enable_pg' => env('PC_ENABLE_PG',true),
    'enable_access' => env('PC_ENABLE_ACCESS', true),
    'passport_location' => env('PC_PASSPORT_LOCATION', 'local'), //no trailing slash
    'app_url' => env('PC_CONSUMER_URL',config('app.url')),
    'user_identifier' => env('PC_USER_IDENTIFIER', 'email'),
    'token_endpoint' => '/oauth/token',
    'post_login_redirect' => '/home',
    'remote_user_identifier_field' => 'id',
    'remote_user_identifier' => 'remote_user_id',
    'user_endpoint' => '/user',
    'user_table' => 'users',
    'log_user_in' => env('PC_LOG_USER_IN', false), //you probably want to set this to true if you have a remote passport install
    'remove_remote_user_identifier_on_rollback' => true, // in case you use username or something dumb. You don't want to drop your username column
    'use_session' => true, //start a user session if you are using the log_user_in option

];