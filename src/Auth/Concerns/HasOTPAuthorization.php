<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Models\OtpToken;

trait HasOTPAuthorization
{
    public function resend()
    {
        //Check if old OTP exists
        $oldToken = OtpToken::where('identifier', request('identifier', '-'))
                        ->findOrFail(request('id'));

        $token = $oldToken->replicateToken()->sendToken();

        return $this->tokenSendResponse($token);
    }

    public function verify()
    {
        $this->validate(request(), [
            'identifier' => 'required',
            'token' => 'required',
        ]);

        if ( !($token = $this->findToken(request('token'), request('identifier'))) ){
            return autoAjax()->error(_('Kód nie je správny.'), 401);
        }

        return autoAjax()->success();
    }
}