<?php

namespace Devdot\DeployArtisan\Commands;

use Illuminate\Console\Command;

class About extends Command
{
    use HasConfigurationTrait;
    use HasSectionDisplayTrait;

    protected $signature = 'deploy:about';

    protected $description = 'Overview of the deployment configuration';

    public function handle()
    {
        // get the local configuration
        if (!$this->loadConfiguration()) {
            return Command::FAILURE;
        }


        $config = $this->configuration;

        // let's display main info
        $this->displaySection('Deployment Configuration', [
            'Role' => $config->role->getStyledString(),
            'Type' => $config->type->getStyledString(),
        ]);

        // let's see if we have credentials loaded
        if ($config->credentials) {
            $this->displaySection('Remote Credentials', [
                'Username' => $config->credentials->username,
                'Password' => '<fg=gray>****</>',
                'Host:' => $config->credentials->host,
                'Port:' => $config->credentials->port,
            ]);
        }

        // and get general configuration

        // $this->line(`git status --branch --porcelain`);
        // $this->line(`git fetch --dry-run -v`);

        return Command::SUCCESS;
    }
}
