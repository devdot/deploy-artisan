<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Contracts\DeployCommand;
use Illuminate\Console\Command;

class ShellCommand implements DeployCommand
{
    public function __construct(
        protected readonly string $shellCommand = '',
    ) {
    }

    public function handle(): int
    {
        $output = [];
        $code = 0;
        exec($this->shellCommand, $output, $code);

        return Command::SUCCESS;
    }

    public function preRunComment(): string
    {
        return 'Execute ' . $this->shellCommand;
    }

    public function postRunComment(): string
    {
        return 'Done';
    }
}
