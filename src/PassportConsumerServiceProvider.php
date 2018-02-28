<?php

namespace Montross50\PassportConsumer;

use Illuminate\Support\ServiceProvider;
use Montross50\PassportConsumer\Commands\ComposerDumpAutoload;
use Montross50\PassportConsumer\Commands\ComposerInstall;
use Montross50\PassportConsumer\Commands\ComposerUpdate;
use Montross50\PassportConsumer\Commands\DockerBuild;
use Montross50\PassportConsumer\Commands\DockerClean;
use Montross50\PassportConsumer\Commands\DockerCleanImages;
use Montross50\PassportConsumer\Commands\DockerDown;
use Montross50\PassportConsumer\Commands\DockerIdeHelper;
use Montross50\PassportConsumer\Commands\DockerMigrate;
use Montross50\PassportConsumer\Commands\DockerRebuild;
use Montross50\PassportConsumer\Commands\DockerRun;
use Montross50\PassportConsumer\Commands\DockerSeed;
use Montross50\PassportConsumer\Commands\DockerStop;
use Montross50\PassportConsumer\Commands\DockerUp;

class PassportConsumerServiceProvider extends ServiceProvider
{
    /**
     * Run service provider boot operations.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/passport-consumer.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('passport-consumer.php');
        } else {
            $publishPath = base_path('config/passport-consumer.php');
        }
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
    }
}
