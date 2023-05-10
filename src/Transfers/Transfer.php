<?php

namespace Devdot\DeployArtisan\Transfers;

use Devdot\DeployArtisan\Contracts\Transfer as TransferInterface;
use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;

class Transfer implements TransferInterface
{
    final protected const STR_CALL_SERVER_SCRIPT = 'php artisan deploy:pull --no-interaction';

    public function __construct(
        protected Command $command,
        protected Configuration $config,
    ) {
    }

    public function getRequiredCredentials(): array
    {
        return [];
    }

    public function pushToServer(): bool
    {
        return false;
    }

    public function callServerShell(string $command): bool
    {
        return false;
    }

    public function callServerScript(?string $gitVerification): bool
    {
        // build verify string
        $verify = '';
        if ($this->config->verifyGit) {
            if ($gitVerification === null) {
                $this->command->error('Git commit verification is enabled but missing!');
                return false;
            }
            $verify = ' --verify-git=' . $gitVerification;
        }

        // now take the command string and add verification
        $command = self::STR_CALL_SERVER_SCRIPT . $verify;

        // use call server shell to call this
        return $this->callServerShell($command);
    }
}
