<?php

use AdminHelpers\Auth\Models\Otp\WhitelistedToken;

function hasOtpEnabled()
{
    return config('admin_helpers.auth.otp.enabled', false) === true;
}

function otpModel()
{
    return Admin::getModelByTable('otp_tokens');
}

if ( !function_exists('isTokenDebug') ) {
    /**
     * Check if the token's are in development mode
     *
     * @return bool
     */
    function isTokenDebug()
    {
        return app()->hasDebugModeEnabled() && isTestEnvironment() && env('TOKEN_DEBUG', false) === true;
    }
}

if ( !function_exists('isTestIdentifier') ) {
    function isTestIdentifier($identifier, $token = null)
    {
        // Enable log in only with whitelisted tokens
        if ( $token && in_array($token, config('admin_helpers.auth.otp.whitelisted_tokens', [])) === false ) {
            return false;
        }

        // All tokens are whitelisted
        if ( isTokenDebug() === true ){
            return true;
        }

        // Check if some token is whitelisted
        if ( in_array($identifier, WhitelistedToken::getParsedIdentifiers()) ){
            return true;
        }

        return in_array($identifier, config('admin_helpers.auth.otp.test_identifiers', []));
    }
}