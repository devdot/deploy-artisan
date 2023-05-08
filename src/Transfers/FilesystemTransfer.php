<?php

namespace Devdot\DeployArtisan\Transfers;

use Devdot\DeployArtisan\Contracts\Transfer;
use Devdot\DeployArtisan\DeployCommands\ShellCommand;
use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;

class FilesystemTransfer implements Transfer
{
    protected ?string $serverDirectory = null;

    public function __construct(
        protected Command $command,
        protected Configuration $config,
    ) {
        $this->serverDirectory = $this->config->credentials->host ?? null;
    }

    public function pushToServer(): bool
    {
        $filename = $this->config->transferFileName;

        // make sure the file is available here
        if (!is_file(base_path($filename))) {
            $this->command->error('File ' . $filename . ' not found, cannot move');
            return false;
        }

        // this will simply move to a defined folder

        $this->command->line('Copy to ' . $this->serverDirectory);

        // make sure the destination exists
        if (!is_dir($this->serverDirectory ?? '.')) {
            $this->command->error('Directory does not exist!');
            return false;
        }

        // make sure the destination is writeable
        if (!is_writeable($this->serverDirectory ?? '.')) {
            $this->command->error('Directory is not writeable!');
            return false;
        }

        // now simply move the file
        $return = copy(base_path($filename), realpath($this->serverDirectory ?? '.') . DIRECTORY_SEPARATOR . $filename);

        if ($return) {
            $this->command->line('Successful');
        } else {
            $this->command->error('Failed copying the file');
        }

        return $return;
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

        // execute the server script
        $str = 'php artisan deploy:pull --no-interaction' . $verify;
        // strip the environment
        $cmd = new ShellCommand('env -i ' . $str);
        $cmd->setParameters([
            'cwd' => $this->serverDirectory ?? '.',
            'env' => [],
        ]);
        echo $str . PHP_EOL;
        $return = $cmd->handle($this->config);

        return $return === 0;
    }
}
