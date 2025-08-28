<?php

namespace AdminHelpers\Auth\Concerns;

use Admin\Eloquent\AdminModel;
use AdminHelpers\Auth\Concerns\HasVerificators;

trait HasOTPAuthorization
{
    use HasVerificators;

    public function resend()
    {
        //Check if old OTP exists
        $oldToken = otpModel()
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
    protected function tokenSendResponse($token, $verified = false)
    {
        $response = autoAjax()->store([
            'otp' => $token->getTokenResponseArray($verified),
        ]);

        // Show message during verification
        if ( $verified === false ) {
            $response->message(
                $token->isTestIdentifier() ? _('Použite testovací OTP token.') : _('Zaslali sme Vám overovací kód.')
            );
        }

        return $response;
    }

    protected function findToken($token, $identifier = null)
    {
        //Skip tokens on DEV
        if ( isTestIdentifier($identifier, $token) ){
            return otpModel()->forceFill([
                'verificator' => $this->getVerificator(),
                'token' => $token,
                'identifier' => $identifier,
            ]);
        }

        $isModel = is_object($identifier) && $identifier instanceof AdminModel;

        $query = [
            'token' => otpModel()->hashToken($token),
        ] + ($isModel ? [
            'table' => $identifier->getTable(),
            'row_id' => $identifier->getKey(),
        ] : [
            'identifier' => $identifier,
        ]);

        return otpModel()->where($query)->where('valid_to', '>=', now())->first();
    }

    protected function createToken($identifier = null, $verificator, $durationMinutes = 15)
    {
        $validTo = now()->addMinutes($durationMinutes);

        $isModel = is_object($identifier) && $identifier instanceof AdminModel;

        $verificator = $verificator ?: $this->getVerificator();

        if ( !$otpModel = otpModel() ) {
            throw new \Exception('OTP model not found. Check OTP configuration in admin_helpers.');
        }

        $token = $otpModel->fill([
                'table' => $isModel ? $identifier->getTable() : null,
                'row_id' => $isModel ? $identifier->getKey() : null,
                'verificator' => $verificator,
                'identifier' => $isModel ? $identifier[$verificator] : $identifier,
                'valid_to' => $validTo,
            ])
            ->generateNewToken();

        $token->save();

        return $token;
    }
}