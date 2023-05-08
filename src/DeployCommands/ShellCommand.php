<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Contracts\DeployCommand;
use Devdot\DeployArtisan\Models\Configuration;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ShellCommand implements DeployCommand
{
    protected Process $process;
    final protected const DEFAULT_TIMEOUT = 360;

    protected string $cwd = '';
    protected int $timeout = self::DEFAULT_TIMEOUT;
    protected bool $silent = false;
    /**
     * @var array<string>
     */
    protected array $env = [];
    protected string $input = '';



    public function __construct(
        protected string $shellCommand = '',
    ) {
    }

    /**
     * @param array{cwd?: string, timeout?: int, silent?: bool, env?: array<string>} $parameters
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        if (isset($parameters['cwd']) && is_string($parameters['cwd'])) {
            $this->cwd = $parameters['cwd'];
        }
        if (isset($parameters['timeout']) && is_numeric($parameters['timeout'])) {
            $this->timeout = (int) $parameters['timeout'];
        }
        if (isset($parameters['silent'])) {
            $this->silent = $parameters['silent'] == true;
        }
        if (isset($parameters['env']) && (is_array($parameters['env']) || is_string($parameters['env']))) {
            $this->env = is_array($parameters['env']) ? $parameters['env'] : [$parameters['env']];
        }
    }

    public function handle(Configuration $config): int
    {
        $this->handlePrepare($config);
        return $this->handleRun($config);
    }

    public function handlePrepare(Configuration $config): void
    {
        // use symfony process
        $this->setupProcess();
    }

    public function handleRun(Configuration $config): int
    {
        // run either loud or quiet
        if ($this->silent) {
            $this->process->run();
        } else {
            // simply print as it comes off the buffer
            $this->process->run(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    echo "\033[31m";
                }
                echo $buffer;
                if ($type === Process::ERR) {
                    echo "\033[0m";
                }
            });
        }

        return $this->process->getExitCode();
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    private function setupProcess(): void
    {
        $this->process = Process::fromShellCommandline(
            $this->shellCommand,
            empty($this->cwd) ? base_path() : $this->cwd,
            $this->env,
            null,
            $this->timeout,
        );
    }

    public function preRunComment(): string
    {
        return 'Execute ' . $this->shellCommand;
    }

    public function postRunComment(): string
    {
        return 'Done';
    }
}
