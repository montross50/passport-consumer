<?php
namespace Montross50\PassportProxy;

use Montross50\PassportProxy\PassportProxyServiceProvider;
use Optimus\ApiConsumer\Provider\LaravelServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    protected function getPackageProviders($app)
    {
        return [PassportProxyServiceProvider::class,LaravelServiceProvider::class];
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
        $this->artisan('migrate', ['--database' => 'mysql']);
        $this->loadLaravelMigrations(['--database' => 'mysql']);
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/passport/database/migrations');
        $this->withFactories(__DIR__.'/factories');
    }
}
