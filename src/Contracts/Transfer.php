<?php

namespace Devdot\DeployArtisan\Contracts;

use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;

interface Transfer
{
    public function __construct(Command $command, Configuration $config);

    /**
     * @return array{username?: bool, password?: bool, host?: bool, port?: bool}
     */
    public function getRequiredCredentials(): array;

    public function pushToServer(): bool;

    public function callServerScript(?string $gitVerification): bool;
}
