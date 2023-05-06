<?php

namespace Devdot\DeployArtisan\Models;

class Credentials
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly string $host,
        public readonly int $port = 22,
    ) {
    }
}
