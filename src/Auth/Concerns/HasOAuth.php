<?php

namespace AdminHelpers\Auth\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasOAuth
{
    /**
     * Into this cache key will be stored authorization request
     *
     * @param  string  $code
     * @return string
     */
    private function getCacheKey($code)
    {
        return 'oauth_crudadmin.'.$code;
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
     * Saves authorization request until the user signs in again. The request is kept in the cache,
     * because the logout before the new sign in invalidates the whole session.
     *
     * @param  mixed $code
     * @param  mixed $request
     * @return void
     */
    protected function saveAuthorizationRequest($code, $request)
    {
        Cache::put($this->getCacheKey($code), $request->all(), now()->addMinutes(15));
    }

    /**
     * Returns authorization request of the code, only once
     *
     * @param  mixed $code
     * @return array
     */
    protected function getAuthorizationRequest($code)
    {
        $params = is_string($code) ? Cache::pull($this->getCacheKey($code)) : null;

        if ( !$params ) {
            abort(401, 'Invalid token');
        }

        return $params;
    }
}