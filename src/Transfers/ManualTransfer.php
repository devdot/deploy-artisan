<?php

namespace Devdot\DeployArtisan\Transfers;

class ManualTransfer extends Transfer
{
    public function pushToServer(): bool
    {
        echo 'Manual transfer does not copy anything!' . PHP_EOL;
        return false;
    }

    public function callServerShell(string $command): bool
    {
        echo 'Manual transfer does not execute anything!' . PHP_EOL;
        return false;
    }
}
