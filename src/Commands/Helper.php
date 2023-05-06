<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\Models\Role;

class Helper
{
    final public const STR_NULL = '<fg=yellow>-</>';
    final public const STR_SECRET = '<fg=gray>****</>';
    final public const STR_TRUE = '<fg=gray>true</>';
    final public const STR_FALSE = '<fg=gray>false</>';

    final public static function styleDeploymentRole(Role $role): string
    {
        return match ($role) {
            Role::Client => '<fg=blue>CLIENT</>',
            Role::Server => '<fg=yellow>SERVER</>',
            Role::Undefined => '<fg=red>UNDEFINED</>',
        };
    }
}
