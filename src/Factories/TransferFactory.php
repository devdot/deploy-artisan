<?php

namespace Devdot\DeployArtisan\Factories;

use Devdot\DeployArtisan\Contracts\Transfer;
use Devdot\DeployArtisan\Models\Type;
use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;
use Devdot\DeployArtisan\Transfers\FilesystemTransfer;
use Devdot\DeployArtisan\Transfers\ManualTransfer;
use Devdot\DeployArtisan\Transfers\SSHTransfer;

class TransferFactory
{
    public static function createFromType(Type $type, Command $command, Configuration $configuration): Transfer|null
    {
        return match ($type) {
            Type::Filesystem => new FilesystemTransfer($command, $configuration),
            Type::SSH => new SSHTransfer($command, $configuration),
            Type::Manual => new ManualTransfer($command, $configuration),
            default => null,
        };
    }
}
