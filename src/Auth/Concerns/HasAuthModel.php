<?php

namespace AdminHelpers\Auth\Concerns;

use Admin;

trait HasAuthModel
{
    /**
     * Guard used for authentication
     *
     * @var string|null
     */
    public $guard = null;

    /**
     * Name of the guard of the authenticated model: the guard of the controller, or
     * admin_helpers.auth.guard (e.g. the Sanctum guard of e-shop clients), null for the default.
     *
     * @return string|null
     */
    public function getAuthGuardName()
    {
        return $this->guard ?: config('admin_helpers.auth.guard');
    }

    /**
     * Returns the auth model into which we are logging in
     *
     * @return AdminModel|null
     */
    public function getAuthModel()
    {
        $guard = $this->getAuthGuardName();

        //Get logged user in case of incomplete registration via Google/Apple socials.
        if ( $client = ($guard ? auth()->guard($guard)->user() : auth()->user()) ) {
            return $client;
        }

        // Model of the project config, e.g. the e-shop Client registered by the project
        if ( $model = config('admin_helpers.auth.model') ) {
            return Admin::getModel(class_basename($model)) ?: new $model;
        }

        // Get default model from config, or from guard defined in controller.
        if ( $guard ) {
            $defaultModel = config('auth.providers.'.config('auth.guards.'.$guard)['provider'])['model'];
        } else {
            $defaultModel = auth()->guard()->getProvider()->getModel();
        }

        // Model from provider by default laravel guard
        return Admin::getModel(
            class_basename($defaultModel)
        );
    }
}
