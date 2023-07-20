<?php

namespace Devdot\DeployArtisan\DeployCommands;

use Devdot\DeployArtisan\Models\Configuration;
use Illuminate\Console\Command;

class ZipTransferFileCommand extends ShellCommand
{
    final protected const SHELL_ZIP_CMD = 'zip -r "%s" -b "%s" "%s"';
    // TODO: this is not working properly
    final protected const SHELL_CLEANUP_CMD = 'rm -f "%s"';

    protected bool $useShell = false;

    protected int $totalFiles = 0;

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
        // remove the old file if it exists
        if (is_file($config->transferFileName)) {
            $this->shellCommand = sprintf(self::SHELL_CLEANUP_CMD, $config->transferFileName);
            echo $this->shellCommand . PHP_EOL;
            parent::handle($config);
        }

        // loop through the files that ought to be added
        foreach ($config->transferFiles as $file) {
            // simply add to a zip archive
            $this->shellCommand = sprintf(self::SHELL_ZIP_CMD, $config->transferFileName, $file, $this->generateRelativePath($file));
            echo $this->shellCommand . PHP_EOL;
            parent::handle($config);
        }
    }

    protected function handleWithPhp(Configuration $config): void
    {
        // remove the old file if it exists
        if (is_file($config->transferFileName)) {
            echo '[PHP] remove ' . $config->transferFileName . PHP_EOL;
            unlink($config->transferFileName);
        }

        // let's start with creating a zip archive
        $zip = new \ZipArchive();
        $zip->open($config->transferFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($config->transferFiles as $name) {
            $relative = $this->generateRelativePath($name);
            if (is_file($name)) {
                echo '[PHP] add file > ' . $relative;
                self::addFileToZip($zip, $name, $relative);
            } elseif (is_dir($name)) {
                echo '[PHP] add dir  > ' . $relative;
                // add directory and everything within
                $count = self::addDirectoryToZip($zip, $name, $relative);
                echo ' [' . $count . ']';
            } else {
                echo '[PHP] skip     - ' . $relative;
            }

            echo PHP_EOL;
        }

        // look for stats
        $this->totalFiles = $zip->numFiles;

        // close the file
        echo '[PHP] close ' . $config->transferFileName . PHP_EOL;
        $zip->close();
    }

    protected static function addDirectoryToZip(\ZipArchive $zip, string $absolutePath, string $relativePath): int
    {
        $count = 1; // first is for the directory itself

        // create the directory
        $zip->addEmptyDir($relativePath);

        // and get the files
        $files = scandir($absolutePath);
        $files = $files === false ? [] : $files;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $absoluteFile = $absolutePath . DIRECTORY_SEPARATOR . $file;
            $relativeFile = $relativePath . DIRECTORY_SEPARATOR . $file;
            if (is_dir($absoluteFile)) {
                $count += self::addDirectoryToZip($zip, $absoluteFile, $relativeFile);
            } else {
                self::addFileToZip($zip, $absoluteFile, $relativeFile);
                $count++;
            }
        }

        return $count;
    }

    protected static function addFileToZip(\ZipArchive $zip, string $absolutePath, string $relativePath): void
    {
        $zip->addFile($absolutePath, $relativePath);
    }

    protected function generateRelativePath(string $path): string
    {
        // simply shorten the supplied path to the base path
        // the paths are assumed to be absolute
        return substr($path, strlen(base_path()) + 1);
    }

    public function preRunComment(): string
    {
        return 'Zip the transfer file using ' . ($this->useShell ? 'shell commands' : 'PHP zip module');
    }

    public function postRunComment(): string
    {
        if ($this->useShell) {
            return parent::postRunComment();
        } else {
            return 'Total files: <fg=gray>' . $this->totalFiles . '</> Done.';
        }
    }
}
