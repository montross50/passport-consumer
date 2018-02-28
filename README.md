## Laravel Artisan Commands Helper.

[![Latest Version on Packagist][ico-version]](https://packagist.org/packages/montross50/passport-consumer)
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://img.shields.io/travis/montross50/passport-consumer.svg?branch=master&style=flat-square)](https://travis-ci.org/montross50/passport-consumer)
[![Total Downloads](https://img.shields.io/packagist/dt/montross50/passport-consumer.svg?style=flat-square)](https://packagist.org/packages/montross50/passport-consumer)

This package will add some helpful commands to artisan. These commands are primarily used in development to reduce typing and increase productivity.

### Installation

~~~
composer require montross50/passport-consumer
~~~

## Available Commands

```
  ach:build            Builds the containers with docker compose
  ach:clean            Cleans up the containers with docker compose
  ach:clean-images     Removes dangling images with docker
  ach:dump             Composer dump autoload in the php workspace container
  ach:ide-helper       Runs the ide-helper in the php workspace container
  ach:install          Composer installs in the php workspace container
  ach:migrate          Migrate the database
  ach:rebuild          Spins up the containers with docker compose and rebuild them
  ach:run              Spins up the containers with docker compose (alias for up)
  ach:seed             Seed your database
  ach:stop             Stops the containers with docker compose
  ach:up               Spins up the containers with docker compose
  ach:update           Composer updates in the php workspace container
```

## Environment configuration

There are several environment variables you can add to your .env that will allow you to customize the commands. This is designed to work out of the box for a standard laravel install with docker and laradock or similiar. Below are the env vars and their defaults.

* ACH_DOCKER_PATH = docker
    * Path to docker executable
* ACH_DOCKER_COMPOSE_PATH = docker-compose
    * Path to docker-compose executable
* ACH_COMPOSER_PATH = composer
    * Path to composer executable
* ACH_NAMESPACE = ach 
    * Namespace that commands resolve at via artisan ie ach:up. Just in case you have something on that namespace
* ACH_PHP_CONTAINER = workspace
    * Container to run php commands in
* ACH_IDE_HELPER_MODELS_OPTIONS = -n
    * Options for ide-helper:models. These options have special chars in them often so artisan won't play nice 

Alternatively you can publish the config file.

~~~
php artisan vendor:publish --provider="Montross50\PassportConsumer\PassportConsumerServiceProvider" --tag=config
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




