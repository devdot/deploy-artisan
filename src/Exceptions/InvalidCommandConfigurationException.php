<?php

namespace Devdot\DeployArtisan\Exceptions;

class InvalidCommandConfigurationException extends InvalidConfigurationException
{
    protected const MESSAGE = 'Command %s is not valid, all commands need to be strings or implement DeployCommand!';
}
