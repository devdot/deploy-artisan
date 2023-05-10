<?php

namespace Devdot\DeployArtisan\Transfers;

use Devdot\DeployArtisan\Contracts\Transfer;
use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;

class ManualTransfer implements Transfer
{
    public function __construct(
        protected Command $command,
        protected Configuration $config,
    ) {
    }

    public function getRequiredCredentials(): array
    {
        return [];
    }

    public function pushToServer(): bool
    {
        echo 'Manual transfer does not copy anything!' . PHP_EOL;
        return true;
    }

    public function callServerScript(?string $gitVerification): bool
    {
        echo 'Manual transfer does not execute anything!' . PHP_EOL;
        return true;
    }
}
