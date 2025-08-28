<?php

namespace AdminHelpers\Auth\Concerns;

use Admin;

trait HasAuthModel
{
    /**
     * Table used for users registration
     *
     * @var string|null
     */
    public $table = null;

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

        if ( $this->table ) {
            return Admin::getModelByTable($this->table);
        }

        // Model from provider by default laravel guard
        return Admin::getModel(
            class_basename(auth()->guard()->getProvider()->getModel())
        );
    }
}