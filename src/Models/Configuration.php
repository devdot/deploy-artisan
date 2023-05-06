<?php

namespace Devdot\DeployArtisan\Models;

use Devdot\DeployArtisan\Exceptions\InvalidCredentialsConfigurationException;
use Devdot\DeployArtisan\Exceptions\InvalidRoleConfigurationException;
use Illuminate\Support\Facades\App;

class Configuration
{
    public Role $role = Role::Undefined;

    public ?Credentials $credentials = null;

    final protected const ENV_ROLE = 'DEPLOY_ROLE';
    final protected const ENV_ROLE_CLIENT = 'client';
    final protected const ENV_ROLE_SERVER = 'server';
    final protected const ENV_CREDENTIALS_USERNAME = 'DEPLOY_CRED_USERNAME';
    final protected const ENV_CREDENTIALS_PASSWORD = 'DEPLOY_CRED_PASSWORD';
    final protected const ENV_CREDENTIALS_HOST = 'DEPLOY_CRED_HOST';
    final protected const ENV_CREDENTIALS_PORT = 'DEPLOY_CRED_PORT';

    /**
     * True when credentials would be available but aren't loaded because the config does not require them
     */
    public bool $credentialsAvailable = false;

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }

    public function isServer(): bool
    {
        return $this->role === Role::Server;
    }

    public function load()
    {
        // load the role
        $this->loadRole();

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
    }

    /**
     * Load role data from the environment file
     */
    protected function loadRole()
    {
        // attempt reading the env value
        $role = env(self::ENV_ROLE);
        if ($role === null) {
            // it is not set, so we assume whatever fits the application environment
            if (App::environment('local')) {
                $role = self::ENV_ROLE_CLIENT;
            } elseif (App::environment('production')) {
                $role = self::ENV_ROLE_SERVER;
            }
        }

        // use match to assign the role
        $this->role = match (strtolower($role)) {
            self::ENV_ROLE_CLIENT => Role::Client,
            self::ENV_ROLE_SERVER => Role::Server,
            default => Role::Undefined,
        };

        // check if the role is undefined, and if so whether that's a good thing or not
        if ($this->role === Role::Undefined && !empty($role)) {
            // this is an issue because the role was set but not recognized
            throw new InvalidRoleConfigurationException($role);
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
        if (empty($username) || empty($password) || empty($host)) {
            throw new InvalidCredentialsConfigurationException(' Username, password and host all have to be set!');
        }

        // we have enough valid input to create the object
        if ($port !== null) {
            $this->credentials = new Credentials($username, $password, $host, (int) $port);
        } else {
            $this->credentials = new Credentials($username, $password, $host);
        }

        return true;
    }

    public function write(): bool
    {

        return false;
    }
}
