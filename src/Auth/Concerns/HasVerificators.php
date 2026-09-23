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
     * Ability to toggle verificator during resend OTP request (eg. phone -> email)
     *
     * @param  \AdminHelpers\Auth\Models\Otp\OtpToken  $oldToken
     * @return array
     */
    public function verificatorTogglerParams($oldToken)
    {
        // No verificator switch has been requested
        if ( request()->has('verificator') === false ) {
            return [];
        }

        // Verificator is the same as the one used to create the token
        if ( ($oldToken->verificator == ($verificator = $this->getVerificator() )) ) {
            return [];
        }

        $identifier = $this->getSwitchedVerificatorIdentifier($oldToken, $verificator);

        // We are not able to deliver the code with the requested method
        if ( !$identifier ) {
            autoAjax()->error(_('Overenie týmto spôsobom nie je momentálne dostupné.'))->throw();
        }

        return [
            'verificator' => $verificator,
            'identifier' => $identifier,
            // Keep the token bound to the very same owner row (or unbound), so a
            // switched token can never point to a foreign account.
            'table' => $oldToken->getAttribute('table'),
            'row_id' => $oldToken->row_id,
            // Hide toggled identifier
            'masked' => true,
        ];
    }

    /**
     * Returns trusted identifier for the switched verification method
     *
     * @param  \AdminHelpers\Auth\Models\Otp\OtpToken  $oldToken
     * @param  string  $verificator
     * @return string|null
     */
    protected function getSwitchedVerificatorIdentifier($oldToken, $verificator)
    {
        // Token is bound to an existing user row (eg. login flow). The destination
        // MUST be taken from that trusted row, never from the request - otherwise an
        // attacker knowing the victim's identifier could redirect the victim's OTP
        // to his own contact and take over the account.
        if ( $row = $oldToken->parentable ) {
            return $row->getAttribute($verificator) ?: null;
        }

        // Token is not bound to any row yet (eg. registration). The destination is
        // provided in the request - this grants no more than requesting a fresh
        // registration OTP for that verificator would, so only a format check is done.
        return $this->getValidatedIdentifier($verificator);
    }

    /**
     * Validates and returns request supplied identifier for the given verificator
     *
     * @param  string  $verificator
     * @return string|null
    */
    public function getValidatedIdentifier($verificator)
    {
        if ( !($identifier = request($verificator)) ) {
            return null;
        }

        // Take rules from config if defined
        if ( !($model = config('admin_helpers.auth.otp.user')) ) {
            return null;
        }

        // Validate and obtain the identifier from the request
        return $model()->validator()
                ->only([$verificator])
                ->merge([ $verificator => 'required' ])
                ->getData()[$verificator];
    }
}