<?php

namespace AdminHelpers\Bootstrap;

use Admin\Core\Bootstrap\BootstrapRequest as BaseBootstrapRequest;
use AdminHelpers\Auth\Utilities\AuthResponse;
use AdminHelpers\Bootstrap\Concerns\HasBuildVersion;

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
     * Sanctum abilities of the token created by auth().
     *
     * @var array<int, string>
     */
    protected $tokenAbilities = ['*'];

    /**
     * Token payload already created by auth() for this instance.
     *
     * @var array|null
     */
    private $issuedToken = null;

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
     * Use the given client instead of the one of the request guard, e.g. right after a login, when
     * the guard of the project may not be the default one.
     *
     * @param  mixed  $client
     * @return self
     */
    public function withClient($client)
    {
        if ($client && $client !== $this->client) {
            $this->client = $client;

            $this->onClient($client);
        }

        return $this;
    }

    /**
     * Set the name of the Sanctum token which auth() creates (once per instance).
     *
     * @param  string  $token
     * @param  array  $abilities  sanctum abilities of the created token
     * @return self
     */
    public function setToken($token, array $abilities = ['*'])
    {
        // Another token name or abilities ask for another token
        if ($token !== $this->token || $abilities !== $this->tokenAbilities) {
            $this->issuedToken = null;
        }

        $this->token = $token;
        $this->tokenAbilities = $abilities;

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
        // A guest has no auth data, AuthResponse needs a user
        if (! $this->client) {
            return [];
        }

        // The token is created only once, auth() may be called by several sections or responses
        // of one request and each call would store another personal access token.
        $data = (new AuthResponse($this->client, $this->issuedToken ? false : $this->token, abilities: $this->tokenAbilities))->toArray();

        if (isset($data['token'])) {
            $this->issuedToken = $data['token'];
        } elseif ($this->issuedToken) {
            $data['token'] = $this->issuedToken;
        }

        return $data;
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
