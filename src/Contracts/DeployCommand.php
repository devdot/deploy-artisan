<?php

namespace Devdot\DeployArtisan\Contracts;

use Devdot\DeployArtisan\Models\Configuration;

interface DeployCommand
{
    public function handle(Configuration $config): int;

    /**
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): void;

    public function preRunComment(): string;

    public function postRunComment(): string;
}
