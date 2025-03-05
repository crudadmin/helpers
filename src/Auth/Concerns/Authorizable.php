<?php

namespace AdminHelpers\Auth\Concerns;

interface Authorizable
{
    /**
     * Finds user model by request data
     *
     * @return \Admin\Eloquent\AdminModel
     */
    public function getAuthModel();

    /**
     * Returns the success response
     *
     * @param  mixed $user
     * @param  string $type
     *
     * @return mixed
     */
    public function authorizedResponse($user, $type = null);
}