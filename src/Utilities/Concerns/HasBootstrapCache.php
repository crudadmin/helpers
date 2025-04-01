<?php

namespace AdminHelpers\Utilities\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasBootstrapCache
{
    /**
     * cache for stores in minutes
     *
     * @var array
     */
    protected $cache = [
        // 'state_key' => 60 * 12,
    ];

    /**
     * Defines cache
     *
     * @return void
     */
    public function cache()
    {
        return $this->cache ?: [];
    }

    /**
     * Returns cached response
     *
     * @param  mixed $method
     * @param  mixed $params
     * @return void
     */
    public function getCachedResponse($method, $params)
    {
        if ( isset($this->cache()[$method]) ) {
            $response = $this->cacheState($method, function () use ($method, $params) {
                return json_encode($this->{$method}($params));
            });

            return json_decode($response, true);
        } else {
            $response = $this->{$method}($params);
        }

        return $response;
    }

    /**
     * Caches state of given method
     *
     * @param  mixed $method
     * @param  mixed $callback
     * @return void
     */
    private function cacheState($method, $callback)
    {
        $parts = [class_basename($this), app()->getLocale(), $method];

        $cache = $this->cache()[$method] ?? [];

        $minutage = is_array($cache) ? ($cache['minutage'] ?? 60) : $cache;

        // Add key to parts if it is callable
        if ( isset($cache['key']) ) {
            $parts[] = $cache['key']();
        }

        $key = implode('.', $parts);

        return Cache::remember($key, now()->addMinutes($minutage), $callback);
    }
}