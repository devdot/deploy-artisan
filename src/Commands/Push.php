<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\DeployCommands\CleanupCommand;
use Devdot\DeployArtisan\Models\Role;
use Illuminate\Console\Command;

class Push extends Command
{
    use HasConfigurationTrait;

    protected $signature = 'deploy:push';

    protected $description = 'Push the deployment package to the server';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration() || !$this->configuration) {
            return Command::FAILURE;
        }

        // check if this matched the role
        if ($this->configuration->role !== Role::Client) {
            // this is not a client, let's make sure this command shall be executed
            $this->warn('You are running deploy:push on role ' . $this->configuration->role->value);
            if (!$this->confirm('Do you wish to continue anyways?', false)) {
                return Command::INVALID;
            }
            $this->newLine();
        }

        // let's say a quick hello
        $this->line('Start deployment <fg=yellow>push</> to server.');

        $git = null;
        if ($this->configuration->verifyGit) {
            // use a command to load the last commit
            $output = [];
            exec('git log -1 --pretty=oneline', $output);
            $git = explode(' ', implode(' ', $output), 2)[0] ?? null;

            // output
            if ($git) {
                $this->line('Last git commit is <fg=gray>' . $git . '</>');
            } else {
                $this->warn('Could not read last git commit!');
            }
        }

        // step 1: run client script
        $this->newLine();
        $this->line('<bg=blue>                    </>');
        $this->line('<bg=blue>  #1 client script  </>');
        $this->line('<bg=blue>                    </>');
        $this->newLine();

        foreach ($this->configuration->clientCommands as $command) {
            $this->line($command->preRunComment());
            $command->handle($this->configuration);
            $this->line($command->postRunComment());
            $this->newLine();
        }

        // step 2: run transfer
        $this->newLine();
        $this->line('<bg=blue>                    </>');
        $this->line('<bg=blue>  #2 transfer       </>');
        $this->line('<bg=blue>                    </>');
        $this->newLine();

        if ($this->configuration->cleanup === false) {
            $this->newLine();
            $this->info('Completed without cleanup');
            return Command::SUCCESS;
        }

        // step 3: cleanup
        $this->newLine();
        $this->line('<bg=blue>                    </>');
        $this->line('<bg=blue>  #3 cleanup        </>');
        $this->line('<bg=blue>                    </>');
        $this->newLine();

        $cleanup = new CleanupCommand();
        $cleanup->handle($this->configuration);

        $this->newLine();
        $this->info('Completed!');

        return Command::SUCCESS;
    }
}
