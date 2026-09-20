<?php

namespace AdminHelpers\Utilities;

use Admin\Core\Utilities\BootstrapRequest as BaseBootstrapRequest;
use AdminHelpers\Auth\Utilities\AuthResponse;
use AdminHelpers\Utilities\Concerns\HasBuildVersion;

class BootstrapRequest extends BaseBootstrapRequest
{
    use HasBuildVersion;

    /**
     * Authentication token.
     */
    public $token;

    /**
     * Logged client/user.
     */
    public $client;

    /**
     * __construct.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setClient();
    }

    /**
     * Set logged client into object.
     *
     * @return void
     */
    private function setClient()
    {
        // Fallback for client() function on old projects
        $this->client = function_exists('client') ? client() : auth()->user();

        if ($this->client) {
            $this->onClient($this->client);
        }
    }

    /**
     * Set authentication token into object.
     *
     * @param  string  $token
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Check if the client is authorized.
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->client ? true : false;
    }

    /**
     * Auth user data.
     *
     * @return array
     */
    public function auth()
    {
        return (new AuthResponse($this->client, $this->token))->toArray();
    }

    /**
     * Determine what to do when client is set.
     *
     * @param  mixed  $client
     * @return void
     */
    public function onClient($client)
    {
        //..
    }
}
