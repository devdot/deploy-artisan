<?php

namespace Devdot\DeployArtisan\Exceptions;

class InvalidTypeConfigurationException extends InvalidConfigurationException
{
    protected const MESSAGE = 'Type %s is invalid!';
}
