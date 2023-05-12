<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\DeployCommands\CleanupCommand;
use Devdot\DeployArtisan\DeployCommands\ShellCommand;
use Devdot\DeployArtisan\Models\Role;
use Illuminate\Console\Command;

class Pull extends Command
{
    use HasConfigurationTrait;

    protected $signature = 'deploy:pull {--verify-git=}';

    protected $description = 'Receive the deployment at the server';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration()) {
            return Command::FAILURE;
        }

        // check if this matched the role
        if ($this->configuration->role !== Role::Server) {
            // this is not a server, let's make sure this command shall be executed
            $this->warn('You are running deploy:pull on role ' . $this->configuration->role->value);
            if (!$this->confirm('Do you wish to continue anyways?', false)) {
                return Command::INVALID;
            }
            $this->newLine();
        }

        // quick intro line
        $this->line('Start deployment <fg=yellow>pull</> at server.');

        // check if we shall verify git
        if ($this->option('verify-git')) {
            $this->line('Attempt git verification, pull first');
            $cmd = new ShellCommand('git pull --rebase');
            $cmd->handle($this->configuration);

            $compare = strval($this->option('verify-git'));

            // use a command to load the last commit
            $cmd = new ShellCommand('git log -1 --pretty=oneline');
            $cmd->setParameters(['silent' => true]);
            $cmd->handle($this->configuration);
            $git = explode(' ', $cmd->getProcess()->getOutput(), 2)[0] ?? null;

            // and now compare
            if ($compare !== $git) {
                $this->line('Last server commit (here): ' . $git);
                $this->line('Last client commit       : ' . $compare);
                $this->error('Git verification mismatch!');
                return Command::FAILURE;
            } else {
                $this->line('Git verified at <fg=gray>' . $git . '</>');
            }
        }

        // check if we have the transfer file
        if (!is_file(base_path($this->configuration->transferFileName))) {
            $this->error('Could not find transfer file ' . $this->configuration->transferFileName);
            return Command::FAILURE;
        }

        // step 1: run server script
        $this->newLine();
        $this->line('<bg=blue>                    </>');
        $this->line('<bg=blue>  #1 server script  </>');
        $this->line('<bg=blue>                    </>');
        $this->newLine();

        foreach ($this->configuration->serverCommands as $command) {
            $this->line($command->preRunComment());
            $command->handle($this->configuration);
            $this->line($command->postRunComment());
            $this->newLine();
        }

        if ($this->configuration->cleanup === false) {
            $this->newLine();
            $this->info('Completed without cleanup');
            return Command::SUCCESS;
        }

        // step 2: cleanup
        $this->newLine();
        $this->line('<bg=blue>                    </>');
        $this->line('<bg=blue>  #2 cleanup        </>');
        $this->line('<bg=blue>                    </>');
        $this->newLine();

        $cleanup = new CleanupCommand();
        $cleanup->handle($this->configuration);

        $this->newLine();
        $this->info('Completed!');

        return Command::SUCCESS;
    }
}
