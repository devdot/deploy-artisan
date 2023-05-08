<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Models\Configuration;
use Illuminate\Console\Command;

class UnzipTransferFileCommand extends ShellCommand
{
    final protected const SHELL_UNZIP_CMD = 'unzip "%s" -d "%s"';
    final protected const SHELL_REMOVE_FILE = 'rm -f "%s"';
    final protected const SHELL_REMOVE_DIR = 'rm -rf "%s"';

    protected bool $useShell = false;

    public function __construct()
    {
        // check if the PHP Class is available, and if so use it
        if (!class_exists('ZipArchive')) {
            $this->useShell = true;
        }
    }

    /**
     * @param array{use_shell?: bool} $parameters
     */
    public function setParameters(array $parameters): void
    {
        // lets see if the use_shell param is set
        if (isset($parameters['use_shell'])) {
            $this->useShell = $parameters['use_shell'] == true;
        }
    }

    public function handle(Configuration $config): int
    {
        // decide which mode this should go
        if ($this->useShell) {
            $this->handleWithShell($config);
        } else {
            $this->handleWithPhp($config);
        }

        return Command::SUCCESS;
    }

    protected function handleWithShell(Configuration $config): void
    {
        // loop through the files and remove the old versions
        foreach ($config->transferFiles as $file) {
            // simply add to a zip archive
            if (file_exists($file)) {
                if (is_file($file)) {
                    $this->shellCommand = sprintf(self::SHELL_REMOVE_FILE, $file);
                } else {
                    $this->shellCommand = sprintf(self::SHELL_REMOVE_DIR, $file);
                }
                echo $this->shellCommand . PHP_EOL;
                parent::handle($config);
            }
        }

        // and now simply run the unzip command
        $this->shellCommand = sprintf(self::SHELL_UNZIP_CMD, $config->transferFileName, base_path());
        echo $this->shellCommand . PHP_EOL;
        parent::handle($config);
    }

    protected function handleWithPhp(Configuration $config): void
    {
        // remove the old files if they exists
        echo '[PHP] remove existing files' . PHP_EOL;
        foreach ($config->transferFiles as $file) {
            // simply add to a zip archive
            echo '    ' . $file . PHP_EOL;
            if (file_exists($file)) {
                if (is_file($file)) {
                    self::removeFile($file);
                } else {
                    self::removeDirectory($file);
                }
            }
        }

        // now simply unzip using the zip archive class
        $zip = new \ZipArchive();
        $zip->open($config->transferFileName, \ZipArchive::RDONLY);
        echo '[PHP] extracting transfer file to ' . base_path() . PHP_EOL;
        $zip->extractTo(base_path());
        $zip->close();
    }

    protected static function removeFile(string $file): void
    {
        unlink($file);
    }

    protected static function removeDirectory(string $directory): void
    {
        // read the directory
        $files = scandir($directory);
        $files = $files === false ? [] : $files;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $absolute = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_file($absolute)) {
                self::removeFile($absolute);
            } elseif (is_dir($absolute)) {
                self::removeDirectory($absolute);
            }
        }
    }

    public function preRunComment(): string
    {
        return 'Unzip the transfer file using ' . ($this->useShell ? 'shell commands' : 'PHP zip module');
    }
}
