<?php

namespace Devdot\DeployArtisan\Models;

/**
 * Define the Deployment Type
 * @author Thomas Kuschan
 * @copyright (c) 2023
 */
enum Type: string
{
    case SSH = 'SSH';
    case Filesystem = 'FILESYSTEM';
    case Manual = 'MANUAL';
    case Undefined = 'UNDEFINED';

    public function getStyledString(): string
    {
        return match ($this) {
            self::Undefined => '<fg=red>UNDEFINED</>',
            default => '<fg=gray>' . $this->value . '</>',
        };
    }
}
