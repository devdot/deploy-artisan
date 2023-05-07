<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Contracts\DeployCommand;
use Devdot\DeployArtisan\Models\Configuration;
use Illuminate\Support\Facades\Artisan;

class ArtisanCommand implements DeployCommand
{
    protected string $command = '';

    public function handle(Configuration $config): int
    {
        $return = Artisan::call($this->command);
        echo Artisan::output();
        return $return;
    }

    public function setParameters(array $parameters): void
    {
        // get the command
        if (isset($parameters['cmd']) && is_string($parameters['cmd'])) {
            $this->command = $parameters['cmd'];
        }

        if (isset($parameters['artisan']) && is_string($parameters['artisan'])) {
            $this->command = $parameters['artisan'];
        }
    }

    public function preRunComment(): string
    {
        return 'Run artisan ' . $this->command;
    }

    public function postRunComment(): string
    {
        return 'Done';
    }
}
