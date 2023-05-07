<?php

namespace Devdot\DeployArtisan\Contracts;

use Devdot\DeployArtisan\Models\Configuration;

interface DeployCommand
{
    public function handle(Configuration $config): int;

    public function preRunComment(): string;

    public function postRunComment(): string;
}
