<?php

namespace AdminHelpers\Utilities;

use AdminHelpers\Auth\Utilities\AuthResponse;

class BootstrapRequest
{
    /**
     * Authentication token
     */
    public $token;

    /**
     * Logged client/user
     */
    public $client;

    /**
     * Called methods, to not return duplicate objects.
     */
    private $called = [];

    /*
     * Which modules should be authenticated
     */
    protected $authenticated = [];

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->setClient();
    }

    /**
     * Set logged client into object
     *
     * @return void
     */
    private function setClient()
    {
        $this->client = client();

        if ( $this->client ) {
            $this->onClient($this->client);
        }
    }

    /**
     * Set authentication token into object
     *
     * @param string $token
     *
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Check if the client is authorized
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->client ? true : false;
    }

    /**
     * Global shared data
     *
     * @return  array
     */
    public function get()
    {
        return [];
    }

    /**
     * Data only for guests
     *
     * @return  array
     */
    public function guest()
    {
        return [];
    }

    /**
     * Data only for authorized user
     *
     * @return  array
     */
    public function authenticated()
    {
        return $this->only($this->authenticated);
    }

    /**
     * Auth user data
     *
     * @return  array
     */
    public function auth()
    {
        return (new AuthResponse($this->client, $this->token))->toArray();
    }

    /**
     * Returns all available data
     *
     * @return  array
     */
    public function all()
    {
        $isAuthorized = $this->isAuthorized();

        $parts = ['get'];

        if ( $isAuthorized ) {
            $parts[] = 'authenticated';
        } else {
            $parts[] = 'guest';
        }

        return $this->only($parts, false);
    }

    /**
     * Returns only given subset of data
     *
     * @param  array  $parts
     * @param  bool  $passKeys
     *
     * @return  array
     */
    public function only($parts, $passKey = true)
    {
        if ( count($parts) == 0 ){
            return $this->all();
        }

        $data = [];

        foreach ($parts as $keyOrMethod => $methodOrParams) {
            $method = is_numeric($keyOrMethod) ? $methodOrParams : $keyOrMethod;
            $params = is_numeric($keyOrMethod) ? [] : $methodOrParams;

            // Check if method is athorized for current request
            if ( $this->isAuthorized() === false && in_array($method, $this->authenticated) ) {
                abort(403, 'You can not load this resource.');
            }

            // Check that each method is called only once
            if ( in_array($method, $this->called) ){
                continue;
            }

            // Check if method exists and is callable
            if ( method_exists($this, $method) ){
                $value = $this->{$method}($params);

                $data[] = $passKey ? [$method => $value] : $value;

                $this->called[] = $method;
            }
        }

        return $this->mergeRecursively($data);
    }

    /**
     * Merge recursively given data parts
     *
     * @param  array  $parts
     *
     * @return  array
     */
    private function mergeRecursively($parts)
    {
        $data = [];

        //Merge arrays recursively
        foreach ($parts as $part) {
            foreach ($part as $key => $value) {
                //Merge two array
                if ( isset($data[$key]) && is_array($data[$key]) && is_array($value) ) {
                    $data[$key] = array_merge($data[$key], $value);
                } else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }


    /**
     * Determine what to do when client is set
     *
     * @param  mixed $client
     *
     * @return void
     */
    public function onClient($client)
    {
        //..
    }
}
