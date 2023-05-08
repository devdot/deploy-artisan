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
        if (!$this->loadConfiguration()) {
            return Command::FAILURE;
        }


        $config = $this->configuration;

        // let's display main info
        $this->displaySection('Deployment Configuration', [
            'Role' => $config->role->getStyledString(),
            'Type' => $config->type->getStyledString(),
            'Transfer File' => $config->transferFileName,
            'Cleanup' => $config->cleanup ? Helper::STR_TRUE : Helper::STR_FALSE,
        ]);

        // show list of files, if they any are set
        if (!empty($config->transferFiles)) {
            $arr = [];
            foreach ($config->transferFiles as $name) {
                // check if it's a file, directory, or missing
                $str = '';
                if (is_file($name)) {
                    $str = Helper::STR_FILE;
                } elseif (is_dir($name)) {
                    $str = Helper::STR_DIRECTORY;
                } else {
                    $str = Helper::STR_MISSING;
                }

                $arr[$name] = $str;
            }
            $this->displaySection('Files/Directories for transfer', $arr);
        }

        // let's see if we have credentials loaded
        if ($config->credentials) {
            $this->displaySection('Remote Credentials', [
                'Username' => $config->credentials->username,
                'Password' => Helper::STR_SECRET,
                'Host:' => $config->credentials->host,
                'Port:' => strval($config->credentials->port),
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

        return Command::SUCCESS;
    }
}
