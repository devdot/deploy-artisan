<?php

namespace Devdot\DeployArtisan\Models;

use Devdot\DeployArtisan\Contracts\DeployCommand;
use Devdot\DeployArtisan\DeployCommands\ShellCommand;
use Devdot\DeployArtisan\Exceptions\InvalidCommandConfigurationException;
use Devdot\DeployArtisan\Exceptions\InvalidCredentialsConfigurationException;
use Devdot\DeployArtisan\Exceptions\InvalidRoleConfigurationException;
use Devdot\DeployArtisan\Exceptions\InvalidTypeConfigurationException;
use Illuminate\Support\Facades\App;
use MirazMac\DotEnv\Writer;

class Configuration
{
    public Role $role = Role::Undefined;
    public Type $type = Type::Undefined;

    public ?Credentials $credentials = null;

    public string $transferFileName = 'deployment.zip';
    public bool $verifyGit = true;
    public bool $cleanup = true;

    /**
     * @var array<string>
     */
    public array $transferFiles = [];

    /**
     * @var array<int, DeployCommand>
     */
    public array $clientCommands = [];

    /**
     * @var array<int, DeployCommand>
     */
    public array $serverCommands = [];

    final protected const ENV_ROLE = 'DEPLOY_ROLE';
    final protected const ENV_TYPE = 'DEPLOY_TYPE';
    final protected const ENV_CREDENTIALS_USERNAME = 'DEPLOY_CRED_USERNAME';
    final protected const ENV_CREDENTIALS_PASSWORD = 'DEPLOY_CRED_PASSWORD';
    final protected const ENV_CREDENTIALS_HOST = 'DEPLOY_CRED_HOST';
    final protected const ENV_CREDENTIALS_PORT = 'DEPLOY_CRED_PORT';

    /**
     * True when credentials would be available but aren't loaded because the config does not require them
     */
    public bool $credentialsAvailable = false;

    protected ?Writer $writer = null;

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }

    public function isServer(): bool
    {
        return $this->role === Role::Server;
    }

    public function load(): void
    {
        // load the role
        $this->loadRole();

        // load the type (also from env)
        $this->loadType();

        // load the credentials
        if ($this->loadCredentials()) {
            // credentials were found, set them to be available
            $this->credentialsAvailable = true;

            // now check if this is a server, and if so, unload credentials
            if ($this->isServer()) {
                $this->credentials = null;
            }
        }

        // now load the config file
        $this->loadConfig();
    }

    /**
     * Load role data from the environment file
     */
    protected function loadRole(): void
    {
        // attempt reading the env value
        $role = env(self::ENV_ROLE);
        if ($role === null) {
            // it is not set, so we assume whatever fits the application environment
            if (App::environment('local')) {
                $role = Role::Client->value;
            } elseif (App::environment('production')) {
                $role = Role::Server->value;
            }
        }

        // use match to assign the role
        $this->role = Role::tryFrom(strtoupper($role)) ?? Role::Undefined;

        // check if the role is undefined, and if so whether that's a good thing or not
        if ($this->role === Role::Undefined && !empty($role)) {
            // this is an issue because the role was set but not recognized
            throw new InvalidRoleConfigurationException($role);
        }
    }

    protected function loadType(): void
    {
        // attempt reading from env
        $type = env(self::ENV_TYPE);

        // use enum to match
        $this->type = Type::tryFrom(strtoupper($type)) ?? Type::Undefined;

        // make sure this isn't an invalid value
        if ($this->type === Type::Undefined && !empty($type) && Type::Undefined->value !== $type) {
            // this could not be matched correctly, meaning the value was wrong
            throw new InvalidTypeConfigurationException($type);
        }
    }

    /**
     * Load credentials from the environment file
     */
    protected function loadCredentials(): bool
    {
        $username = strval(env(self::ENV_CREDENTIALS_USERNAME));
        $password = strval(env(self::ENV_CREDENTIALS_PASSWORD));
        $host = strval(env(self::ENV_CREDENTIALS_HOST));
        $port = env(self::ENV_CREDENTIALS_PORT);

        // now validate those inputs
        $port = empty($port) ? null : $port;
        if ($port !== null && !is_numeric($port)) {
            throw new InvalidCredentialsConfigurationException(' Port ' . $port . ' is not numeric!');
        }

        // check if perhaps they all just aren't set
        if (empty($username) && empty($password) && empty($host) && empty($port)) {
            return false;
        }

        // now lets see if anything is missing or empty
        if (empty($username) || empty($host)) {
            throw new InvalidCredentialsConfigurationException(' Username and host all have to be set!');
        }

        // make sure the password is never empty, but null
        if (empty($password)) {
            $password = null;
        }

        // we have enough valid input to create the object
        if ($port !== null) {
            $this->credentials = new Credentials($username, $password, $host, (int) $port);
        } else {
            $this->credentials = new Credentials($username, $password, $host);
        }

        return true;
    }

    /**
     * Load data from config file
     */
    protected function loadConfig(): void
    {
        $config = config('deployment');
        // TODO: Throw exception when config file not present
        // if the config is null, let's load from our own file
        if ($config === null) {
            $config = require __DIR__ . '/../../config/deployment.php';
        }

        // use the values and write them to ourself
        if (isset($config['transfer_file_name'])) {
            $this->transferFileName = strval($config['transfer_file_name']);
        }

        if (isset($config['verify_git'])) {
            $this->verifyGit = $config['verify_git'] == true;
        }

        if (isset($config['cleanup'])) {
            $this->cleanup = $config['cleanup'] == true;
        }

        if (isset($config['transfer_files']) && is_array($config['transfer_files'])) {
            $this->transferFiles = [];
            foreach ($config['transfer_files'] as $file) {
                // allow the non-existing paths to be added anyways, they might get generated during the script
                $realpath = realpath($file);
                $this->transferFiles[] = $realpath === false ? $file : $realpath;
            }
        }

        // load commands
        $this->clientCommands = $this->prepareCommands($config['client'] ?? []);
        $this->serverCommands = $this->prepareCommands($config['server'] ?? []);
    }

    /**
     * Prepare a list of commands into proper executable command objects
     * @param array<string|array<string>> $commands
     * @return array<int, DeployCommand>
     */
    protected function prepareCommands(array $commands): array
    {
        $return = [];

        // lets go through the list of input commands
        foreach ($commands as $command) {
            // first check if this is an array
            $params = [];
            $object = null;
            if (is_array($command)) {
                $params = $command;
                $command = array_shift($params) ?? '';
            }

            // check if it is a classname
            if (is_subclass_of($command, DeployCommand::class)) {
                // yes it is, so we construct it and add to the array
                $object = new $command();
            } elseif (class_exists($command)) {
                // throw an exception because it does not have the interface implemented
                throw new InvalidCommandConfigurationException($command);
            } else {
                // simply put the string into a shell command
                $object = new ShellCommand($command);
            }

            // see if we have params to add
            if (!empty($params)) {
                $object->setParameters($params);
            }

            // add to the array
            $return[] = $object;
        }

        return $return;
    }

    protected function getWriter(): Writer
    {
        if ($this->writer === null) {
            $this->writer = new Writer(App::environmentFilePath());
        }
        return $this->writer;
    }

    public function write(): bool
    {
        $success = true;
        $success = $this->writeRole() && $success;
        $success = $this->writeType() && $success;
        $success = $this->writeCredentials() && $success;

        return $success;
    }

    public function writeRole(): bool
    {
        $writer = $this->getWriter();
        $writer->set(self::ENV_ROLE, $this->role->value);

        return $writer->write();
    }

    public function writeType(): bool
    {
        $writer = $this->getWriter();
        $writer->set(self::ENV_TYPE, $this->type->value);

        return $writer->write();
    }

    public function writeCredentials(): bool
    {
        if ($this->credentials === null) {
            return false;
        }

        $writer = $this->getWriter();
        $writer->set(self::ENV_CREDENTIALS_USERNAME, $this->credentials->username, true);
        $writer->set(self::ENV_CREDENTIALS_PASSWORD, $this->credentials->password, true);
        $writer->set(self::ENV_CREDENTIALS_HOST, $this->credentials->host);
        $writer->set(self::ENV_CREDENTIALS_PORT, $this->credentials->port);

        return $writer->write();
    }
}
