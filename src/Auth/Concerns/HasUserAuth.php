<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Concerns\HasPhoneFormat;
use AdminHelpers\Auth\Concerns\HasVerifiedMethods;

trait HasUserAuth
{
    use HasPhoneFormat,
        HasVerifiedMethods;

    /**
     * Get guard
     *
     * @return AuthGuard
     */
    public function getGuard()
    {
        return auth()->guard($this->guard);
    }
}
