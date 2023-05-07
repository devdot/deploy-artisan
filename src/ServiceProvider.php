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
        ]);
    }
}
