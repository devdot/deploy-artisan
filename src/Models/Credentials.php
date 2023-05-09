<?php

namespace Devdot\DeployArtisan\Models;

/**
 * Hold credentials for the different transfer methods
 * @property non-empty-string $username
 * @property non-empty-string|null $password
 * @property non-empty-string $host
 * @property int $port
 *
 * @author Thomas Kuschan
 * @copyright (c) 2023
 */
class Credentials
{
    /**
     * @param non-empty-string $username
     * @param non-empty-string|null $password
     * @param non-empty-string $host
     * @param int $port
     */
    public function __construct(
        public readonly string $username,
        public readonly string|null $password,
        public readonly string $host,
        public readonly int $port = 22,
    ) {
    }
}
