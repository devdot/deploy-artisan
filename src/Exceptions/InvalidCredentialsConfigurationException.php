<?php

namespace Devdot\DeployArtisan\Exceptions;

class InvalidCredentialsConfigurationException extends InvalidConfigurationException
{
    protected const MESSAGE = 'Credentials are not configured correctly! %s';
}
