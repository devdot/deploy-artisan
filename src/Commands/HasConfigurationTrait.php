<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\Exceptions\InvalidConfigurationException;
use Devdot\DeployArtisan\Models\Configuration;

trait HasConfigurationTrait
{
    protected ?Configuration $configuration = null;

    public function loadConfiguration(bool $catchExceptions = true, bool $showExceptions = true): bool
    {
        $this->configuration = new Configuration();

        // let's see if we shall catch exceptions
        if (!$catchExceptions) {
            $this->configuration->load();
            return true;
        }

        // now that we are loading with exception, we go for it
        try {
            $this->configuration->load();
            return true;
        } catch (InvalidConfigurationException $e) {
            if ($showExceptions) {
                // simply display this using the Command class
                $this->error($e->getMessage());
            }
            return false;
        }
    }
}
