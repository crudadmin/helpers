<?php

namespace AdminHelpers\Auth\Concerns;

trait HasVerifiedMethods
{
    /**
     * Add login method as verified, eg. email, phone, etc.
     *
     * @param  string $method
     * @param  string $identifier
     *
     * @return void
     */
    public function addVerified($method, $identifier)
    {
        $this->fixOldVerifiedFormat();

        $verified = $this->verified?->toArray() ?: [];

        if ( $method ) {
            $verified[$method][] = $identifier;

            $verified[$method] = array_values(array_unique($verified[$method]));
        }

        $this->verified = $verified;

        return $this;
    }

    /**
     * Check if login method is verified, eg. email, phone, etc.
     *
     * @param  string $method
     * @param  string $identifier
     *
     * @return void
     */
    public function isVerified($method, $identifier)
    {
        $identifier = $identifier ?: $this->getAttribute($method);

        $this->fixOldVerifiedFormat();

        // If no identifier has been passed, deny access.
        if ( !$identifier ) {
            return false;
        }

        //If not verification method has been passed, and user exists. We can pass true.
        if ( $this->exists && !$method ){
            return true;
        }

        return in_array($identifier, $this->verified[$method] ?? []);
    }

    public function fixOldVerifiedFormat()
    {
        $verified = $this->verified?->toArray() ?: [];

        if ( count($verified) === 0 ) {
            return;
        }

        // Is assoc array (old verified format)
        if ( array_keys($verified) !== range(0, count($verified) - 1) ) {
            return;
        }

        $arr = [];

        foreach ( $verified as $verificator ) {
            $arr[$verificator][] = $this->getAttribute($verificator);
        }

        $this->verified = $arr;

        return true;
    }
}