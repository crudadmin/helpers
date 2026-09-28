<?php

namespace AdminHelpers\Utilities;

use Admin\Core\Utilities\BootstrapRequest as BaseBootstrapRequest;
use AdminHelpers\Auth\Utilities\AuthResponse;
use AdminHelpers\Utilities\Concerns\HasBuildVersion;

/**
 * Bootstrap data of the client application.
 *
 * The logged client is resolved in the constructor and onClient() runs right there, so an
 * instance belongs to one request. Create it per request (new, container make() or a real-time
 * facade, which Laravel Octane resets before every request). Never bind it as a singleton, never
 * resolve it in a service provider and never keep it in a static property: under Laravel Octane
 * or a queue worker the next requests would get the client of the request which created it.
 */
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
     * Resolve the logged client of the current request.
     *
     * Not lazy on purpose: onClient() of projects has side effects (last activity, platform,
     * language), which have to run when the bootstrap request is created.
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
     * Public methods of this class which are sections. Every other public method of this class,
     * its parent and its traits (only, all, cache, setToken, getBundlePath...) is infrastructure
     * and can not be requested as a section, even when a project overrides it.
     *
     * @var array<int, string>
     */
    protected $baseSections = ['get', 'guest', 'authenticated', 'auth'];

    /**
     * Sections are the public methods added by the project, and the base sections.
     *
     * @param  string  $method
     * @return bool
     */
    protected function canLoadSection($method)
    {
        if (parent::canLoadSection($method) === false || str_starts_with($method, '__')) {
            return false;
        }

        $method = strtolower($method);

        // Data of the authenticated user only for an authorized request
        if ($method === 'authenticated' && $this->isAuthorized() === false) {
            return false;
        }

        if (in_array($method, array_map('strtolower', $this->baseSections))) {
            return true;
        }

        $infrastructure = array_map(
            fn ($reflection) => strtolower($reflection->getName()),
            (new \ReflectionClass(self::class))->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        return in_array($method, $infrastructure) === false;
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
