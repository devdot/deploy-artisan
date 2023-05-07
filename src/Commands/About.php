<?php

namespace Devdot\DeployArtisan\Commands;

use Illuminate\Console\Command;

class About extends Command
{
    use HasConfigurationTrait;
    use HasSectionDisplayTrait;

    protected $signature = 'deploy:about';

    protected $description = 'Overview of the deployment configuration';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration() || !$this->configuration) {
            return Command::FAILURE;
        }


        $config = $this->configuration;

        // let's display main info
        $this->displaySection('Deployment Configuration', [
            'Role' => $config->role->getStyledString(),
            'Type' => $config->type->getStyledString(),
            'Transfer File' => $config->transferFileName,
            'Cleanup' => $config->cleanup,
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

        // and show client commands
        if (!empty($config->clientCommands)) {
            $this->displaySection('Client Commands', []);
            foreach ($config->clientCommands as $command) {
                $this->line('  > ' . $command->preRunComment());
            }
        }

        // server commands
        if (!empty($config->serverCommands)) {
            $this->displaySection('Server Commands', []);
            foreach ($config->serverCommands as $command) {
                $this->line('  > ' . $command->preRunComment());
            }
        }

        // and get general configuration

        // $this->line(`git status --branch --porcelain`);
        // $this->line(`git fetch --dry-run -v`);

        return Command::SUCCESS;
    }
}
