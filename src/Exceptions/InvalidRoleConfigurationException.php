<?php

namespace Devdot\DeployArtisan\Exceptions;

class InvalidRoleConfigurationException extends InvalidConfigurationException
{
    protected const MESSAGE = 'Role %s is invalid!';
}
