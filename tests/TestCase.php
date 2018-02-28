<?php
namespace Tests;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Montross50\PassportConsumer\PassportConsumerServiceProvider;
use Optimus\ApiConsumer\Provider\LaravelServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    protected function getPackageProviders($app)
    {
        return [PassportConsumerServiceProvider::class,LaravelServiceProvider::class,PassportServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function setUp()
    {
        ini_set('memory_limit', '512M');

        parent::setUp();
        $this->loadLaravelMigrations(['--database' => 'mysql']);
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/passport/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');
        Passport::routes();
        Passport::loadKeysFrom(__DIR__ . '/data');
    }

    public function bootstrapUserRoute($user)
    {
        Route::group(['middleware' => []], function () use ($user) {
            Route::get('/user', function () use ($user) {
                return \Illuminate\Support\Facades\Response::json($user);
            });
        });
    }

    public function bootstrapTokenRoute($user, $endpoint)
    {
        Route::group(['middleware' => []], function () use ($endpoint, $user) {
            Route::post($endpoint, function () use ($user) {
                return \Illuminate\Support\Facades\Response::json([
                    'access_token' => 'foo',
                    'expires_in' => 599,
                    'refresh_token' => 'bar',
                    'user'=>$user
                ]);
            });
        });
    }
}
