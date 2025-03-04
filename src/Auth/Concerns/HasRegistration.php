<?php

namespace AdminHelpers\Auth\Concerns;

use AutoAjax\AutoAjax;
use Illuminate\Http\Response;
use AdminHelpers\Auth\Events\UserRegistered;
use AdminHelpers\Auth\Concerns\HasOTPAuthorization;
use AdminHelpers\Auth\Concerns\HasResponse;

trait HasRegistration
{
    use HasOTPAuthorization,
        HasResponse;

    public function registerOTP()
    {
        $model = $this->getAuthModel();

        $data = $this->getValidatedOtpData($model);

        // If error response is present, throw it.
        if ( $data instanceof Response || $data instanceof AutoAjax ) {
            return $data;
        }

        // 1. If no verificator is available
        // 2. Or user is already logged in via social network, and his method is verified after social login (eg. mail)
        if ( !($verificator = $this->getVerificator()) || $model->isVerified($verificator) ) {
            $token = otpModel()->fill([ 'verificator' => $verificator ]);

            return $this->tokenSendResponse($token, true);
        } else {
            $identifier = $data[$verificator] ?? null;

            $token = $this->createToken($identifier, $verificator, 60)->sendToken();

            return $this->tokenSendResponse($token);
        }
    }

    public function registerOTPVerifyRegister()
    {
        $model = $this->getAuthModel();

        $isNewEntry = $model->exists === false;

        $data = $this->getValidatedRegisterData($model);

        // Verify registration OTP or logged state
        $this->verifyRegistrationOtp($model);

        $model = $this->createUser($model, $data, $isNewEntry);

        if ( $isNewEntry ){
            event(new UserRegistered($model));
        }

        return $this->makeAuthResponse($model, 'register')
                    ->message($this->getSuccessMessage());
    }

    public function getSuccessMessage()
    {
        return _('Boli ste úspešne registrovaní!');
    }

    protected function verifyRegistrationOtp($model, $destroyToken = true)
    {
        $verificator = request('verificator');

        //If no verificator has been passed, but app requires verificator and user is not verified yet.
        if ( !$verificator && $this->getVerificator() && $model->verified->count() == 0 ) {
            return autoAjax()->error(_('Zopakujte prosím proces registrácie.'), 401)->throw();
        }

        //Uf user is logged in already and verification method is already verified as well, skip.
        //Or user is not logged in. Thus is not verified.
        elseif ( $verificator && $model->isVerified($verificator) == false ) {
            if ( !($token = $this->findToken(request('token'), request($verificator))) ){
                return autoAjax()->error(_('Prihlasovací kód nie je správny.'), 401)->throw();
            }

            $model->addVerified($token->verificator);
        }

        // Delete token after verification
        if ( isset($token) && $destroyToken ) {
            $token->delete();
        }

        return isset($token) ? $token : null;
    }
}