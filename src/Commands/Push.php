<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\DeployCommands\CleanupCommand;
use Devdot\DeployArtisan\DeployCommands\ShellCommand;
use Devdot\DeployArtisan\Models\Credentials;
use Devdot\DeployArtisan\Models\Role;
use Devdot\DeployArtisan\Models\Type;
use Devdot\DeployArtisan\Transfers\FilesystemTransfer;
use Devdot\DeployArtisan\Transfers\ManualTransfer;
use Devdot\DeployArtisan\Transfers\SSHTransfer;
use Illuminate\Console\Command;

class Push extends Command
{
    use HasConfigurationTrait;

    protected $signature = 'deploy:push';

    protected $description = 'Push the deployment package to the server';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration()) {
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
            $cmd = new ShellCommand('git log -1 --pretty=oneline');
            $cmd->setParameters(['silent' => true]);
            $cmd->handle($this->configuration);
            $git = explode(' ', $cmd->getProcess()->getOutput(), 2)[0] ?? null;

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

        // create the transfer from enum
        $transfer = match ($this->configuration->type) {
            Type::Filesystem => new FilesystemTransfer($this, $this->configuration),
            Type::SSH => new SSHTransfer($this, $this->configuration),
            Type::Manual => new ManualTransfer($this, $this->configuration),
            default => null,
        };

        // make sure the transfer is not null
        if ($transfer === null) {
            $this->error('Could not create transfer object for type ' . $this->configuration->type->value);
            return Command::FAILURE;
        }

        // let's see if we need to look for a password
        if ($this->configuration->type === Type::SSH && $this->configuration->credentials &&  $this->configuration->credentials->password === null) {
            // create new credentials by asking for password
            $pwd = $this->secret('Enter password for ' . $this->configuration->credentials->username . '@' . $this->configuration->credentials->host);
            $credentials = new Credentials(
                $this->configuration->credentials->username,
                strval($pwd) !== '' ? strval($pwd) : null,
                $this->configuration->credentials->host,
                $this->configuration->credentials->port,
            );
            $this->configuration->credentials = $credentials;
        }

        $this->line('Start transfer of type ' . $this->configuration->type->value);
        if (!$transfer->pushToServer()) {
            $this->error('failed!');
            return Command::FAILURE;
        }

        $this->line('Execute the server script');
        if (!$transfer->callServerScript($git)) {
            $this->error('failed!');
            return Command::FAILURE;
        }

        $this->info('Transfer successful');

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
