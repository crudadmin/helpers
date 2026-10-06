<?php

namespace AdminHelpers\Bootstrap;

use Admin\Core\Bootstrap\BootstrapRequest as BaseBootstrapRequest;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/**
 * Selects the bootstrap request class of the client application.
 *
 * Projects with several applications (customer, courier, store owner...) send the application
 * type in a header (`app-type` by default) and map it to their AppRequest classes, which usually
 * extend one default AppRequest:
 *
 *   'bootstrap' => [
 *       'class' => App\Utilities\Bootstrap\AppRequest::class,
 *       'app_types' => ['courier' => App\Utilities\Bootstrap\CourierAppRequest::class],
 *   ]
 *
 * An unknown or missing type falls back to the default class, never to null. Stateless: every
 * make() creates a new instance, which belongs to the current request only.
 */
class BootstrapResolver
{
    /**
     * Application type sent by the client.
     *
     * @return string|null
     */
    public static function appType()
    {
        $header = config('admin_helpers.bootstrap.header', 'app-type');

        return $header ? (request()->header($header) ?: null) : null;
    }

    /**
     * Bootstrap request class of the application type (of the request by default).
     *
     * @param  string|null  $appType
     * @return class-string<BaseBootstrapRequest>
     */
    public static function getClass($appType = null)
    {
        $appType = $appType ?: static::appType();

        $types = config('admin_helpers.bootstrap.app_types', []) ?: [];

        $class = ($appType && isset($types[$appType]) ? $types[$appType] : null)
            ?: config('admin_helpers.bootstrap.class')
            ?: BootstrapRequest::class;

        if (! is_a($class, BaseBootstrapRequest::class, true)) {
            throw new InvalidArgumentException('Bootstrap request ['.$class.'] must extend '.BaseBootstrapRequest::class.'.');
        }

        return $class;
    }

    /**
     * New bootstrap request of the application type, resolving the logged client of the request.
     *
     * Sections are computed only once per instance, so create a new instance for every response
     * instead of sharing one.
     *
     * @param  string|null  $appType
     * @return BaseBootstrapRequest
     */
    public static function make($appType = null)
    {
        return app()->make(static::getClass($appType));
    }

    /**
     * Bootstrap route returning the requested sections, ?only=locale,auth (all sections without it).
     *
     * @param  string  $uri
     * @param  string  $controller
     * @return \Illuminate\Routing\Route
     */
    public static function routes($uri = 'bootstrap', $controller = BootstrapController::class)
    {
        return Route::any($uri, [$controller, 'index']);
    }
}
