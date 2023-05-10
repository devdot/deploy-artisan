<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\Factories\TransferFactory;
use Illuminate\Console\Command;

class ServerExec extends Command
{
    use HasConfigurationTrait;

    protected $signature = 'deploy:server-exec {cmd}';

    protected $description = 'Execute a command through the configured transfer on the server';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration()) {
            return Command::FAILURE;
        }

        // get the command
        $command = $this->argument('cmd');
        if (!is_string($command)) {
            $this->error('The command must be a string!');
            return Command::INVALID;
        }

        // get the transfer
        $transfer = TransferFactory::createFromType($this->configuration->type, $this, $this->configuration);
        if ($transfer === null) {
            $this->error('Transfer is not configured correctly!');
            return Command::FAILURE;
        }

        // now we can get to work
        $this->line('Using transfer ' . $this->configuration->type->value);
        $this->newLine();

        return $transfer->callServerShell($command) ? Command::SUCCESS : Command::FAILURE;
    }
}
