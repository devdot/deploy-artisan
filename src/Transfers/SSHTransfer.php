<?php

namespace Devdot\DeployArtisan\Transfers;

use Devdot\DeployArtisan\Contracts\Transfer;
use Devdot\DeployArtisan\DeployCommands\ShellCommand;
use Illuminate\Console\Command;
use Devdot\DeployArtisan\Models\Configuration;

class SSHTransfer implements Transfer
{
    final protected const SSH_COPY_CMD = 'sshpass -p \'%password\' scp -P %port \'%transfer_file\' \'%username@%host:%host_dir/%transfer_file\'';
    final protected const SSH_EXEC_CMD = 'sshpass -p \'%password\' ssh -p %port \'%username@%host\' \'cd %host_dir && %cmd\'';

    public function __construct(
        protected Command $command,
        protected Configuration $config,
    ) {
    }

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
        echo $this->prepareString(self::SSH_COPY_CMD) . PHP_EOL;

        // create command object and run it
        $cmd = new ShellCommand($this->prepareString(self::SSH_COPY_CMD, true));
        $return = $cmd->handle($this->config);

        return $return === 0;
    }

    public function callServerScript(?string $gitVerification): bool
    {
        if ($this->config->credentials === null) {
            echo 'Credentials are missing!';
            return false;
        }

        $verify = '';
        if ($this->config->verifyGit) {
            if ($gitVerification === null) {
                $this->command->error('Git commit verification is enabled but missing!');
                return false;
            }
            $verify = ' --verify-git=' . $gitVerification;
        }

        // server string
        $server = 'php artisan deploy:pull --no-interaction' . $verify;

        // build the string
        echo $this->prepareString(self::SSH_EXEC_CMD) . PHP_EOL;
        echo $server . PHP_EOL;

        // create command object and run it
        $str = strtr($this->prepareString(self::SSH_EXEC_CMD, true), [
            '%cmd' => $server,
        ]);
        $cmd = new ShellCommand($str);
        $return = $cmd->handle($this->config);

        return $return === 0;
    }
}
