<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Concerns\HasPhoneFormat;
use AdminHelpers\Auth\Concerns\HasUserVerificator;

trait HasUserAuth
{
    use HasPhoneFormat,
        HasUserVerificator;

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
