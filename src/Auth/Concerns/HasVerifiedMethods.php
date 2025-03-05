<?php

namespace AdminHelpers\Auth\Concerns;

trait HasVerifiedMethods
{
    /**
     * Add login method as verified, eg. email, phone, etc.
     *
     * @param  string $method
     *
     * @return void
     */
    public function addVerified($method)
    {
        $verified = $this->verified?->toArray() ?: [];

        if ( $method && in_array($method, $verified) == false ) {
            $this->verified = array_merge($verified, [ $method ]);
        }

        return $this;
    }

    /**
     * Check if login method is verified, eg. email, phone, etc.
     *
     * @param  string $method
     *
     * @return void
     */
    public function isVerified($method)
    {
        //If not verification method has been passed, and user exists. We can pass true.
        if ( $this->exists && !$method ){
            return true;
        }

        return in_array($method, $this->verified?->toArray() ?: []);
    }
}