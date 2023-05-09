<?php

namespace Devdot\DeployArtisan\Models;

use Devdot\DeployArtisan\DeployCommands\ShellCommand;

class System
{
    public bool $isDetected = false;

    public bool $hasShellAccess = false;
    public bool $hasPhp = false;
    public bool $hasComposer = false;
    public bool $hasNpm = false;
    public bool $hasNode = false;
    public bool $hasSsh = false;
    public bool $hasScp = false;
    public bool $hasSshPass = false;
    public bool $hasZip = false;
    public bool $hasGit = false;
    public bool $hasGitRepository = false;
    public bool $hasVite = false;
    public bool $hasWebpack = false;
    public bool $hasPhpZip = false;

    public function detect(): void
    {
        $this->isDetected = true;

        // let's see if we can access the shell
        // run through our shell wrapper and if that fails, nothing will work
        $cmd = new ShellCommand('whoami');
        $cmd->setParameters(['silent' => true]);
        try {
            // run this and see if it fails
            $cmd->handle(new Configuration());
            if (!empty($cmd->getProcess()->getOutput()) || !empty($cmd->getProcess()->getErrorOutput())) {
                $this->hasShellAccess = true;
            }
        } catch (\Exception $e) {
            $this->hasShellAccess = false;
        }

        if ($this->hasShellAccess) {
            // let's test all the shell commands now
            $this->hasPhp       = $this->detectShellCommand('php -v');
            $this->hasComposer  = $this->detectShellCommand('composer -V');
            $this->hasNpm       = $this->detectShellCommand('npm -v');
            $this->hasNode      = $this->detectShellCommand('node -v');
            $this->hasSsh       = $this->detectShellCommand('ssh -V');
            $this->hasScp       = $this->detectShellCommand('scp') || $this->detectShellCommand('command -v scp');
            $this->hasSshPass   = $this->detectShellCommand('sshpass -V');
            $this->hasZip       = $this->detectShellCommand('zip');
            $this->hasGit       = $this->detectShellCommand('git --version');
            $this->hasGitRepository = $this->detectShellCommand('git status');
        }

        // check laravel environment for known things
        if (file_exists(base_path('package.json'))) {
            $scripts = json_decode(file_get_contents(base_path('package.json')) ?: '')->scripts ?? [];
            $scripts = $scripts instanceof \stdClass ? (array) $scripts : $scripts;
            $scripts = is_array($scripts) ? $scripts : [$scripts];

            $this->hasVite = in_array(['vite', 'vite build'], $scripts);
            $this->hasWebpack = in_array(['mix', 'mix --production', 'mix watch'], $scripts);
        }

        $this->hasVite = $this->hasVite ?: file_exists(base_path('vite.config.js'));
        $this->hasWebpack = $this->hasWebpack ?: file_exists(base_path('webpack.config.js'));

        $this->hasPhpZip = class_exists('ZipArchive');
    }

    protected function detectShellCommand(string $command): bool
    {
        $cmd = new ShellCommand($command);
        $cmd->setParameters(['silent' => true]);
        try {
            $cmd->handle(new Configuration());
            return $cmd->getProcess()->getExitCode() === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
