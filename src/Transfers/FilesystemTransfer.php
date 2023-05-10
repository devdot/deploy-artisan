<?php

namespace Devdot\DeployArtisan\Transfers;

use Devdot\DeployArtisan\DeployCommands\ShellCommand;
use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;

class FilesystemTransfer extends Transfer
{
    protected ?string $serverDirectory = null;

    public function __construct(Command $command, Configuration $config)
    {
        parent::__construct($command, $config);
        $this->serverDirectory = $this->config->credentials->host ?? null;
    }

    public function getRequiredCredentials(): array
    {
        return [
            'host' => true,
        ];
    }

    public function pushToServer(): bool
    {
        $filename = $this->config->transferFileName;

        // this will simply move to a defined folder
        $this->command->line('FS> Copy to ' . $this->serverDirectory);

        // make sure the file is available here
        if (!is_file(base_path($filename))) {
            $this->command->error('File ' . $filename . ' not found, cannot move');
            return false;
        }

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

    public function callServerShell(string $command): bool
    {
        $this->command->line('FS> ' . $command);

        // simply use a shell command
        // prepend env function to clean env variables in call
        $cmd = new ShellCommand('env -i ' . $command);

        // set the working directory (cwd) to the directory
        $cmd->setParameters([
            'cwd' => $this->serverDirectory ?? '.',
            'env' => [],
        ]);

        return $cmd->handle($this->config) === 0;
    }
}
