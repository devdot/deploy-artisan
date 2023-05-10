<?php

namespace Devdot\DeployArtisan;

use Illuminate\Foundation\Console\AboutCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        AboutCommand::add('devdot/deploy-artisan', fn () => [
            'available' => true,
        ]);

        // install all our commands
        $this->commands([
            Commands\About::class,
            Commands\Push::class,
            Commands\Pull::class,
            Commands\Configure::class,
            Commands\ServerExec::class,
        ]);

        // publish the config file
        $this->publishes([
            __DIR__ . '/../config/deployment.php' => config_path('deployment.php'),
        ], 'deploy-artisan-config');
    }
}
