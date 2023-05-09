<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\Models\Configuration;
use Devdot\DeployArtisan\Models\Credentials;
use Devdot\DeployArtisan\Models\Role;
use Devdot\DeployArtisan\Models\System;
use Devdot\DeployArtisan\Models\Type;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Devdot\DeployArtisan\DeployCommands\ArtisanCommand;
use Devdot\DeployArtisan\DeployCommands\UnzipTransferFileCommand;
use Devdot\DeployArtisan\DeployCommands\ZipTransferFileCommand;

class Configure extends Command
{
    use HasSectionDisplayTrait;
    use HasConfigurationTrait;
    use HasSystemDetectionTrait;

    protected $signature = 'deploy:configure';

    protected $description = 'Configure the deployment process';

    protected Configuration $newConfig;

    final protected const NOT_DETECTED =  ' [not detected]';

    public function handle(): int
    {
        // get the local configuration
        if (!$this->loadConfiguration()) {
            return Command::FAILURE;
        }

        $this->newConfig = clone $this->configuration;

        $this->line('This command will take you through the setup of deploy-artisan.');
        $this->info('It is recommended to run first from server perspective and second from client perspective.');

        // start asking questions

        // ask what should be configured
        $mode = $this->choice('Which options should be configured?', [
            0 => 'from server perspective',
            1 => 'from client perspective',
            2 => 'everything',
            3 => 'only this environment',
            4 => 'only transfer',
        ], $this->newConfig->isServer() ? 0 : 1);

        // main switch for this
        match ($mode) {
            'everything' => [
                $this->configureThisEnvironment(),
                $this->configureFromServer(),
                $this->configureFromClient(),
                $this->configureTransfer(),
            ],
            'only this environment' => $this->configureThisEnvironment(),
            'only transfer' => $this->configureTransfer(),
            'from client perspective' => [
                $this->configureThisEnvironment(),
                $this->configureFromClient(),
                $this->configureTransfer(),
            ],
            'from server perspective' => [
                $this->configureThisEnvironment(),
                $this->configureFromServer(),
            ],
        };


        return Command::SUCCESS;
    }

    /**
     * @param array{?username: bool, ?password: bool, ?host: bool, ?port: bool} $required Require them if true, simply ask if false, and don't even ask if null/undefined
     * @return \Devdot\DeployArtisan\Models\Credentials
     */
    private function askForCredentials(array $required): Credentials
    {
        $username = $this->newConfig->credentials ? $this->newConfig->credentials->username : '';
        $password = $this->newConfig->credentials ? $this->newConfig->credentials->password : '';
        $host = $this->newConfig->credentials ? $this->newConfig->credentials->host : '';
        $port = Credentials::DEFAULT_PORT;

        if ($this->newConfig->credentials === null && $this->newConfig->credentialsAvailable) {
            // old values will not show up because this is server
            $this->warn('Previous values will not appear when role is configured to SERVER');
            $this->warn('Credentials should not be configured at the server! Write nonsense credentials if this is production.');
        }

        if (isset($required['username'])) {
            $username = $this->askForCredential('Username', $username, $required['username']);
        }
        if (isset($required['password'])) {
            $password = $this->askForCredential('Password', $password, $required['password']);
        }
        if (isset($required['host'])) {
            $host = $this->askForCredential('Host/Path', $host, $required['host']);
        }
        if (isset($required['port'])) {
            $port = $this->askForCredential('Port', $port, $required['port']);
        }

        // build credentials
        return new Credentials(
            empty($username) ? 'default' : $username,
            empty($password) ? 'default' : $password,
            empty($host) ? 'default' : $host,
            $port === null ? Credentials::DEFAULT_PORT : (int) $port
        );
    }

    private function askForCredential(string $question, string|int|null $previous, bool $required): string|null
    {
        $answer = '';
        do {
            $answer = $this->askWithCompletion($question . ', previous: [' . strval($previous) . ']', [$previous]);
        } while ($required === true && empty($answer));

        return $answer === null ? null : strval($answer);
    }

    private function configureThisEnvironment(): void
    {
        $this->newLine();
        $this->line('<bg=blue>Configure this environment</>');
        $this->newLine();

        $this->line('App environment: ' . App::environment());

        // determine the default role
        $defaultRole = $this->configuration ? $this->configuration->role : Role::Undefined;
        if ($defaultRole === Role::Undefined) {
            $defaultRole = App::environment('local') ? Role::Client : Role::Server;
        }

        // ask for the role
        $role = $this->choice('Which role should this environment have?', [
            'server',
            'client',
            'none (leave undefined)',
        ], $defaultRole === Role::Server ? 0 : 1);
        $this->newConfig->role = Role::tryFrom(strtoupper($role)) ?? Role::Undefined;

        // and lets write
        if ($this->newConfig->writeRole()) {
            $this->info('Write successful');
        } else {
            $this->error('Write failed');
        }
    }

    private function configureTransfer(): void
    {
        $this->newLine();
        $this->line('<bg=blue>Configure transfer</>');
        $this->newLine();

        $system = null;
        if ($this->newConfig->isClient()) {
            $this->line('This environment is a client');
            $system = $this->getDetectedSystem();
        } else {
            $this->line('This environment is NOT a client');

            // ask if we should use auto-detection anyways
            if ($this->confirm('Use auto-detection anyways?', false)) {
                $system = $this->getDetectedSystem();
            }
        }

        // ask for transfer types
        $type = $this->choice('Which transfer type should be configured?', [
            Type::SSH->value => 'SSH' . ($system && !$system->hasSsh ? self::NOT_DETECTED : ''),
            Type::Filesystem->value => 'Filesystem (locally available)',
            Type::Manual->value => 'No automatic transfer',
        ]);
        $this->newConfig->type = Type::tryFrom($type) ?? Type::Undefined;

        // check if we need credentials
        if ($this->newConfig->type === Type::SSH) {
            $this->newConfig->credentials = $this->askForCredentials([
                'username' => true,
                'password' => true,
                'host' => true,
                'port' => false,
            ]);
        }

        if ($this->newConfig->type === Type::Filesystem) {
            $this->newConfig->credentials = $this->askForCredentials([
                'host' => true,
            ]);
        }

        // write
        if ($this->newConfig->writeType() && ($this->newConfig->writeCredentials() || $this->newConfig->credentials === null)) {
            $this->info('Write successful');
        } else {
            $this->error('Write failed');
        }

        // and now ask some config options related to transport
        $this->newConfig->cleanup = $this->confirm('Should cleanup be triggered after transfer?', $this->newConfig->cleanup);
        $name = $this->askWithCompletion('Name of the transfer file [' . $this->newConfig->transferFileName . ']', [$this->newConfig->transferFileName]);
        $this->newConfig->transferFileName = empty($name) ? $this->newConfig->transferFileName : $name;

        // write config
        if ($this->newConfig->writeConfig()) {
            $this->info('Write successful');
        } else {
            $this->error('Write failed');
        }
    }

    private function configureFromClient(): void
    {
        $this->newLine();
        $this->line('<bg=blue>Configure from client</>');
        $this->newLine();

        $system = null;
        if ($this->newConfig->isClient()) {
            $this->line('This environment is a client');
            $system = $this->getDetectedSystem();
        } else {
            $this->line('This environment is NOT a client');

            // ask if we should use auto-detection anyways
            if ($this->confirm('Use auto-detection anyways?', false)) {
                $system = $this->getDetectedSystem();
            }
        }

        if ($system) {
            // display what we found
            $this->displaySystem($system);
        } else {
            $system = new System();
        }

        $this->warn('There not much to do, the configuration should be generated from the server, and then transfer from the client');
    }

    private function configureFromServer(): void
    {
        $this->newLine();
        $this->line('<bg=blue>Configure from server</>');
        $this->newLine();

        $system = null;
        if ($this->newConfig->isServer()) {
            $this->line('This environment is a server');
            $system = $this->getDetectedSystem();
        } else {
            $this->line('This environment is NOT a server');

            // ask if we should use auto-detection anyways
            if ($this->confirm('Use auto-detection anyways?', false)) {
                $system = $this->getDetectedSystem();
            }
        }

        if ($system) {
            // display what we found
            $this->displaySystem($system);
        } else {
            $system = new System();
        }

        // let's start collecting data
        $this->line('Do you want to have any of these recommended commands in the client script:');
        $server = []; // commands to run at the server
        $client = []; // commands to run at the client
        $files = [];
        $useGit = false;
        $useNode = false;
        $useComposer = false;

        if (!$system->hasPhpZip && !$system->hasZip) {
            $this->warn('Neither zip command nor PHP zip extension found!');
        }
        if ($this->confirm('Unzip files', $system->hasPhpZip || $system->hasZip)) {
            $server[] = [UnzipTransferFileCommand::class, 'use_shell' => $system->hasZip];
            $client[] = [ZipTransferFileCommand::class, 'use_shell' => $system->hasZip];
        }

        if ($system->hasShellAccess || $this->confirm('Shell access was not detected, do you want to add those commands anyways?')) {
            if ($this->confirm('git pull', $system->hasGit && $system->hasGitRepository)) {
                $server[] = 'git pull';
                $client[] = 'git push';
                $useGit = true;
            } else {
                $files[] = app_path();
                $files[] = base_path('bootstrap');
                $files[] = config_path('config');
                $files[] = database_path('factories');
                $files[] = database_path('migrations');
                $files[] = database_path('seeders');
                $files[] = lang_path();
                $files[] = public_path();
                $files[] = resource_path();
                $files[] = base_path('routes');
            }

            if ($this->confirm('composer install --optimize-autoloader --no-dev', $system->hasComposer)) {
                $server[] = 'composer install --optimize-autoloader --no-dev';
                $useComposer = true;
            } else {
                $client[] = 'composer install --optimize-autoloader --no-dev';
                $files[] = base_path('bootstrap');
                $files[] = base_path('vendor');
            }

            if ($this->confirm('npm install --omit=dev', ($system->hasNode && $system->hasNpm))) {
                $server[] = 'npm install --omit=dev';
                $useNode = true;
            }

            if ($system->hasVite) {
                if ($this->confirm('vite build', $useNode)) {
                    $server[] = 'vite build';
                } else {
                    $client[] = 'vite build';
                    $files[] = public_path('build');
                }
            }

            if ($system->hasWebpack) {
                if ($this->confirm('npm run production', $useNode)) {
                    $server[] = 'npm run production';
                } else {
                    $client[] = 'npm run production';
                    $files[] = public_path('public/css');
                    $files[] = public_path('public/js');
                }
            }
        } else {
            $this->warn('Skipping any that require shell access because it is not available.');
        }

        // we are done with those sort of questions
        $files = array_values(array_unique($files));
        $client = array_reverse($client);

        // now let's see if they want artisan commands
        // run in batch
        $artisan = [
            'migrate --step --force',
            'view:cache',
            'config:cache',
            'route:cache',
            'event:cache',
        ];
        foreach ($artisan as $cmd) {
            if ($this->confirm('Add ' . $cmd . ' ?', true)) {
                $server[] = [ArtisanCommand::class, 'cmd' => $cmd];
            }
        }

        // special case up/down
        if ($this->confirm('Add artisan up/down?', true)) {
            $server = [
                [ArtisanCommand::class, 'cmd' => 'down'],
                ...$server,
            ];
            $server[] = [ArtisanCommand::class, 'cmd' => 'up'];
        }

        // show results
        $this->newLine();
        $this->line('The following files will be part of the transfer:');
        $this->components->bulletList($files);
        $this->line('Commands to run at the client:');
        $this->components->bulletList(array_map(fn($val) => var_export((is_array($val) ? $val[0] : $val), true), $client));
        $this->line('[transfer command, will be configured soon]');
        $this->line('Commands to run at the server:');
        $this->components->bulletList(array_map(fn($val) => var_export((is_array($val) ? $val[0] : $val), true), $server));

        if ($this->confirm('Confirm and save to config file.', true)) {
            // write these using the config
            $this->newConfig->setClientCommands($client);
            $this->newConfig->setServerCommands($server);
            $this->newConfig->transferFiles = $files;
            $this->newConfig->verifyGit = $useGit;

            if ($this->newConfig->writeConfig()) {
                $this->info('Write successful');
            } else {
                $this->error('Write failed');
            }
        } elseif ($this->confirm('Re-enter data?', false)) {
            $this->configureFromServer();
        }
    }

    private function displaySystem(System $system): void
    {
        $this->displaySection('auto-detected system from shell', [
            'PHP' => Helper::bool($system->hasPhp),
            'Composer' => Helper::bool($system->hasComposer),
            'NPM' => Helper::bool($system->hasNpm),
            'Node' => Helper::bool($system->hasNode),
            'SSH' => Helper::bool($system->hasSsh),
            'SCP' => Helper::bool($system->hasScp),
            'SSHPass' => Helper::bool($system->hasSshPass),
            'Zip' => Helper::bool($system->hasZip),
            'Git' => Helper::bool($system->hasGit),
            'Git Repository' => Helper::bool($system->hasGitRepository),
        ]);

        $this->displaySection('auto-detected system from Laravel', [
            'PHP Zip Extension' => Helper::bool($system->hasPhpZip),
            'Webpack' => Helper::bool($system->hasWebpack),
            'Vite' => Helper::bool($system->hasVite),
        ]);
    }
}
