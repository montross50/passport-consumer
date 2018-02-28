<?php

namespace Montross50\PassportConsumer;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Montross50\PassportConsumer\Handlers\DefaultPostAuthorizeCallback;
use Montross50\PassportConsumer\Handlers\PostAuthorizeCallbackInterface;

class PassportConsumerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
        $configPath = __DIR__ . '/../config/passport-consumer.php';
        $publishPath = config_path('passport-consumer.php');

        $this->publishes([$configPath => $publishPath], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/passport-consumer.php';
        $this->mergeConfigFrom($configPath, 'passport-consumer');
        if ($location = config('passport-consumer.passport_location') !== 'local') {
            $this->app->singleton('apiconsumer', function () use ($location) {
                return new Client([
                    'base_uri' => $location,
                ]);
            });
        }
        $this->app->bind(UserRepository::class, function ($app) {
            return new UserRepository($app['config']);
        });

        $this->app->bind(PostAuthorizeCallbackInterface::class, function ($app) {
            $closure = function ($args) {
                return Response::json(current($args));
            };
            return new DefaultPostAuthorizeCallback($closure);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
