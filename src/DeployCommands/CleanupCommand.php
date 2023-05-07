<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Contracts\DeployCommand;
use Devdot\DeployArtisan\Models\Configuration;

class CleanupCommand implements DeployCommand
{
    public function handle(Configuration $config): int
    {
        // remove the transport file
        if (file_exists($config->transferFileName)) {
            echo 'Remove ' . $config->transferFileName . PHP_EOL;
            unlink($config->transferFileName);
        }

        return 0;
    }

    public function setParameters(array $parameters): void
    {
    }

    public function preRunComment(): string
    {
        return 'Cleanup';
    }

    public function postRunComment(): string
    {
        return 'Done';
    }
}
