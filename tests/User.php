<?php

namespace Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Montross50\PassportConsumer\HasRemoteTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasRemoteTokens;

    protected $fillable = [
        'name', 'email', 'password'
    ];

    protected $hidden = [
      'password'
    ];
}
