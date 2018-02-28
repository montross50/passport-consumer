## Laravel Passport Consumer.

[![Latest Version on Packagist][ico-version]](https://packagist.org/packages/montross50/passport-consumer)
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://img.shields.io/travis/montross50/passport-consumer.svg?branch=master&style=flat-square)](https://travis-ci.org/montross50/passport-consumer)
[![Total Downloads](https://img.shields.io/packagist/dt/montross50/passport-consumer.svg?style=flat-square)](https://packagist.org/packages/montross50/passport-consumer)

This package lets you consume laravel passport local or remote with either the password grant flow or the authorization code flow. The aim is to allow you to focus on your app and leave oauth to passport and the consumption of said oauth to this package.

### Installation

~~~
composer require montross50/passport-consumer
~~~

## Environment configuration

Publish the config file:

~~~
php artisan vendor:publish --provider="Montross50\PassportConsumer\PassportConsumerServiceProvider" --tag=config
~~~

There are a LOT of config options. Probably too many. The package should work out of the box with a default laravel install aside from defining the required env variables somewhere. The main config options to take note of are:

* enable_pg
    * If set to true then the password grant routes are loaded.
* enable_access
    * If set to true then the authorization code routes are loaded 
* passport_location
    * If set to local then it is assumed the given app is the app with passport installed. If not it is expected that this value is your passport server url.
* log_user_in
    * If set to true the following happens:
        * User is retrieved from user_endpoint 
        * If remote passport:
            * Find local user for remote user
            * Create local user using defaults and data from remote if not found
        * Log user in using session guard
        * The access_token and refresh_token will be in the session.

Required env variables:

* PC_PASSPORT_SECRET_PG
    * The passport secret access key for you password grant client
* PC_PASSPORT_SECRET_ACCESS
    * The passport secret access key for you authorization code client
* PC_PASSPORT_ID_PG
    * The passport client id for your password grant client
* PC_PASSPORT_ID_ACCESS
    * The passport client id for you authorization code client

### User Model

If you are working with a remote passport install then add the `Montross50\PassportConsumer\HasRemoteTokens` Trait to your Users model. 

The log_user_in functionality will create users to pair with a remote user if the local user cannot be found. Default values and fields are defined on the trait and must be overridden if you need to add more defaults for new user.



### Run Migrations

This package adds an api_token and configurable remote_user_id field to your users model. This only happens if you have your package configured for remote passport.

~~~
php artisan migrate
~~~



## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email sch43228@gmail.com instead of using the issue tracker.

## Credits

- Trent Schmidt  

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/montross50/passport-consumer.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/montross50/passport-consumer/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/montross50/passport-consumer.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/montross50/passport-consumer.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/montross50/passport-consumer.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/montross50/passport-consumer
[link-travis]: https://travis-ci.org/montross50/passport-consumer
[link-scrutinizer]: https://scrutinizer-ci.com/g/montross50/passport-consumer/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/montross50/passport-consumer
[link-downloads]: https://packagist.org/packages/montross50/passport-consumer
[link-author]: https://github.com/montross50
[link-contributors]: ../../contributors




