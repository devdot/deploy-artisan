<?php

namespace Devdot\DeployArtisan\Transfers;

use Devdot\DeployArtisan\DeployCommands\ShellCommand;

class SSHTransfer extends Transfer
{
    final protected const SSH_COPY_CMD = 'sshpass -p \'%password\' scp -P %port \'%transfer_file\' \'%username@%host:%host_dir/%transfer_file\'';
    final protected const SSH_EXEC_CMD = 'sshpass -p \'%password\' ssh -p %port \'%username@%host\' \'cd %host_dir && %cmd\'';

    public function getRequiredCredentials(): array
    {
        return [
            'username' => true,
            'password' => true,
            'host' => true,
            'port' => false,
        ];
    }

    public function prepareString(string $str, bool $withPassword = false): string
    {
        if ($this->config->credentials === null) {
            return $str;
        }

        // lets make sure the host is formatted correctly
        $host = $this->config->credentials->host;
        $dir = '.';
        if (strpos($host, ':') !== false) {
            [$host, $dir] = explode(':', $host, 2);
        }

        return strtr($str, [
            '%transfer_file' => $this->config->transferFileName,
            '%username' => $this->config->credentials->username,
            '%password' => $withPassword ? $this->config->credentials->password : '****',
            '%host' => $host,
            '%host_dir' => $dir,
            '%port' => $this->config->credentials->port,
        ]);
    }

    public function pushToServer(): bool
    {
        if ($this->config->credentials === null) {
            echo 'Credentials are missing!';
            return false;
        }

        // build the string
        $this->command->line('SSH> ' . $this->prepareString(self::SSH_COPY_CMD));

        // create command object and run it
        $cmd = new ShellCommand($this->prepareString(self::SSH_COPY_CMD, true));
        $return = $cmd->handle($this->config);

        return $return === 0;
    }

    public function callServerShell(string $command): bool
    {
        if ($this->config->credentials === null) {
            echo 'Credentials are missing!';
            return false;
        }

        // build the string
        $this->command->line('SSH> ' . $this->prepareString(self::SSH_EXEC_CMD));
        $this->command->line('SSH> %cmd: ' . $command);

        // create command object and run it
        $str = strtr($this->prepareString(self::SSH_EXEC_CMD, true), [
            '%cmd' => $command,
        ]);
        $cmd = new ShellCommand($str);

        return $cmd->handle($this->config) === 0;
    }
}
