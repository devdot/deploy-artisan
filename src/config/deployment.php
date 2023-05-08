<?php

use Devdot\DeployArtisan\DeployCommands\ArtisanCommand;
use Devdot\DeployArtisan\DeployCommands\CleanupCommand;
use Devdot\DeployArtisan\DeployCommands\UnzipTransferFileCommand;
use Devdot\DeployArtisan\DeployCommands\ZipTransferFileCommand;

return [
    /**
     * Paths to the files and directories, that should be packed into the transfer file
     * Use Laravel path helpers (base_path, public_path etc)
     */
    'transfer_files' => [
        public_path('build'),
        public_path('js'),
        public_path('css'),
    ],

    /**
     * Name of the file that is sent from client to server
     */
    'transfer_file_name' => 'deployment.zip',

    /**
     * Verify the git status when deploying from client to server
     */
    'verify_git' => true,

    /**
     * Run cleanup routine after push/pull
     */
    'cleanup' => true,

    /**
     * Commands to run on the client
     */
    'client' => [
        'git push',
        'vite build',
        [ZipTransferFileCommand::class, 'use_shell' => false],
    ],

    /**
     * Commands to run on the server
     */
    'server' => [
        [ArtisanCommand::class, 'cmd' => 'down'],
        'git pull',
        [UnzipTransferFileCommand::class, 'use_shell' => false],
        'composer install --optimize-autoloader --no-dev',
        [ArtisanCommand::class, 'cmd' => 'migrate --step --force'],
        [ArtisanCommand::class, 'cmd' => 'view:cache'],
        [ArtisanCommand::class, 'cmd' => 'config:cache'],
        [ArtisanCommand::class, 'cmd' => 'route:cache'],
        [ArtisanCommand::class, 'cmd' => 'event:cache'],
        [ArtisanCommand::class, 'cmd' => 'up'],
    ],
];
