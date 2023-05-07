<?php

use Devdot\DeployArtisan\DeployCommands\ShellCommand;

return [
    /**
     * Name of the file that is sent from client to server
     */
    'transfer_file_name' => 'deployment.zip',

    /**
     * Run cleanup routine after push/pull
     */
    'cleanup' => true,

    /**
     * Commands to run on the client
     */
    'client' => [
        'vite build',
        'git checkout public/build/assets/.htaccess',
        'rm -f deployment.zip',
        'zip -r deployment.zip public/build',
        'rm -f deployment.zip',
        ShellCommand::class,
    ],

    /**
     * Commands to run on the server
     */
    'server' => [

    ],
];
