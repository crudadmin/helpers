<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Concerns\HasPhoneFormat;

trait HasUserAuth
{
    use HasPhoneFormat;

    public function getGuard()
    {
        return auth()->guard($this->guard);
    }

    public function addVerified($method)
    {
        $verified = $this->verified?->toArray() ?: [];

        if ( $method && in_array($method, $verified) == false ) {
            $this->verified = array_merge($verified, [ $method ]);
        }

        return $this;
    }

    public function isVerified($method)
    {
        //If not verification method has been passed, and user exists. We can pass true.
        if ( $this->exists && !$method ){
            return true;
        }

        return in_array($method, $this->verified?->toArray() ?: []);
    }
}
