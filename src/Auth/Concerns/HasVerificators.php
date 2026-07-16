<?php

namespace AdminHelpers\Auth\Concerns;

trait HasVerificators
{
    /**
     * Get verificator
     *
     * @return  string
     */
    public function getVerificator($user = null)
    {
        $defaultVerificator = env('AUTH_VERIFICATOR', 'email');
        $verificator = request('verificator', $defaultVerificator);

        // If verificator is switched to SMS mode, but no phone number is present, then use email.
        if ( $user && $verificator == 'phone' && !$user->{$verificator} && $user->email ){
            return 'email';
        }

        return $verificator;
    }

    /**
     * Abilility to toggle verificator during resend OTP request
     *
     * @param  mixed $oldVerificator
     * @return void
     */
    public function verificatorTogglerParams($oldVerificator)
    {
        if ( $oldVerificator != ($verificator = $this->getVerificator()) && request()->has($verificator) ) {
            return [
                'verificator' => $verificator,
                'identifier' => request($verificator),
            ];
        }

        return [];
    }
}