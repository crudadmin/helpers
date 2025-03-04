<?php

namespace AdminHelpers\Auth\Concerns;

trait HasResponse
{
    protected function makeAuthResponse($user, $type = null)
    {
        $user->getAuthGuard()->setUser($user);

        return $this->loginResponse($user, $type);
    }
}