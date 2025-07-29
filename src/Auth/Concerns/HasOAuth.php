<?php

namespace AdminHelpers\Auth\Concerns;

trait HasOauth
{
    /**
     * Into this session key will be stored authorization request
     *
     * @return void
     */
    private function getSessionKey()
    {
        return 'oauth_crudadmin';
    }

    /**
     * Returns oauth config for given client id
     *
     * @param  mixed $clientId
     * @param  mixed $key
     * @return void
     */
    public function getOauthConfig($clientId, $key = null)
    {
        $apps = config('admin_helpers.auth.oauth.apps', []);

        if ($key) {
            return $apps[$clientId][$key] ?? null;
        }

        return $apps[$clientId];
    }

    /**
     * Checks if app is registered in config
     *
     * @param  mixed $clientId
     * @return void
     */
    protected function checkApp($clientId)
    {
        $apps = config('admin_helpers.auth.oauth.apps', []);

        if (!array_key_exists($clientId, $apps)) {
            abort(403, 'Unauthorized app.');
        }
    }

    /**
     * Saves authorization request to logged user session
     *
     * @param  mixed $code
     * @param  mixed $request
     * @return void
     */
    protected function saveAuthorizationRequest($code, $request)
    {
        session()->put($this->getSessionKey().'.'.$code, $request->all());
        session()->save();
    }

    /**
     * Returns authorization request from logged user session
     *
     * @param  mixed $code
     * @return void
     */
    protected function getAuthorizationRequest($code)
    {
        $params = session()->get($this->getSessionKey().'.'.$code);

        if ( !$params ) {
            abort(401, 'Invalid token');
        }

        // Forget request from session
        session()->forget($this->getSessionKey().'.'.$code);
        session()->save();

        return $params;
    }
}