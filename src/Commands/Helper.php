<?php

namespace Devdot\DeployArtisan\Commands;

class Helper
{
    final public const STR_NULL = '<fg=yellow>-</>';
    final public const STR_SECRET = '<fg=gray>****</>';
    final public const STR_TRUE = '<fg=gray>true</>';
    final public const STR_FALSE = '<fg=gray>false</>';

    final public const STR_FILE = '<fg=gray>file</>';
    final public const STR_DIRECTORY = '<fg=gray>directory</>';
    final public const STR_MISSING = '<fg=red>missing</>';

    public static function bool(bool $in): string
    {
        return $in ? self::STR_TRUE : self::STR_FALSE;
    }
}
