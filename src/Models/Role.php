<?php

namespace Devdot\DeployArtisan\Models;

enum Role: string
{
    case Server = 'SERVER';
    case Client = 'CLIENT';
    case Undefined = 'UNDEFINED';

    public function getStyledString(): string
    {
        return match ($this) {
            self::Client => '<fg=gray>CLIENT</>',
            self::Server => '<fg=yellow>SERVER</>',
            self::Undefined => '<fg=red>UNDEFINED</>',
        };
    }
}
