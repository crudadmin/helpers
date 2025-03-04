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

    public function loginResponse($user, $type = null);
}