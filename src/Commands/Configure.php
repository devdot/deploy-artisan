<?php

namespace Devdot\DeployArtisan\Commands;

use Illuminate\Console\Command;

class Configure extends Command
{
    use HasConfigurationTrait;

    protected $signature = 'deploy:configure';

    protected $description = 'Configure the deployment process';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration()) {
            return Command::FAILURE;
        }

        // start asking questions


        return Command::SUCCESS;
    }
}
