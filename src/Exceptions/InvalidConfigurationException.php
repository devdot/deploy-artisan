<?php

namespace Devdot\DeployArtisan\Exceptions;

class InvalidConfigurationException extends \Exception
{
    final protected const PRETEXT = 'Invalid Configuration: ';
    protected const MESSAGE = '%s';

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(self::PRETEXT . sprintf(static::MESSAGE, $message), $code, $previous);
    }
}
