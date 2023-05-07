<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Contracts\DeployCommand;
use Devdot\DeployArtisan\Models\Configuration;
use Illuminate\Console\Command;

class ShellCommand implements DeployCommand
{
    public function __construct(
        protected string $shellCommand = '',
    ) {
    }

    public function setParameters(array $parameters): void
    {
        // we don't expect parameters
    }

    public function handle(Configuration $config): int
    {
        // make sure the command is redirecting stderror
        if (substr($this->shellCommand, -5) !== ' 2>&1') {
            $this->shellCommand .= ' 2>&1';
        }

        $output = [];
        $code = 0;
        exec($this->shellCommand, $output, $code);

        // simply output the lines
        foreach ($output as $line) {
            echo $line . PHP_EOL;
        }

        return $code;
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
