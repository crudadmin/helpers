<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Concerns\HasVerificators;
use Admin;

trait HasOTPAuthorization
{
    use HasVerificators;

    public function getOtpModel()
    {
        return Admin::getModelBytable('otp_tokens');
    }

    public function resend()
    {
        //Check if old OTP exists
        $oldToken = $this->getOtpModel()
                        // Find by identifier
                        ->where('identifier', request('identifier', '-'))
                        // Find by exact OTP
                        ->when(request('id'), function($query, $id){
                            return $query->where($query->qualifyColumn('id'), $id);
                        })
                        ->firstOrFail();

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

    /**
     * Returns response of successfuly sent token
     *
     * @return  AutoAjax
     */
    protected function tokenSendResponse($token)
    {
         return autoAjax()->store([
            'otp' => $token->getTokenResponseArray(),
        ])->message(
            $token->isTestIdentifier()
                ? _('Použite testovací OTP token.')
                : _('Zaslali sme Vám overovací kód.')
        );
    }
}