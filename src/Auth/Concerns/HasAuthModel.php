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
     * Returns the auth model into which we are logging in
     *
     * @return AdminModel|null
     */
    public function getAuthModel()
    {
        //Get logged user in case of incomplete registration via Google/Apple socials.
        if ( $client = auth()->user() ) {
            return $client;
        }

        // Get default model from config, or from guard defined in controller.
        if ( $this->guard ) {
            $defaultModel = config('auth.providers.'.config('auth.guards.'.$this->guard)['provider'])['model'];
        } else {
            $defaultModel = auth()->guard()->getProvider()->getModel();
        }

        // Model from provider by default laravel guard
        return Admin::getModel(
            class_basename($defaultModel)
        );
    }
}