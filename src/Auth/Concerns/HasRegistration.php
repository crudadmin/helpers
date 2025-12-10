<?php

namespace AdminHelpers\Auth\Concerns;

use Admin;
use AutoAjax\AutoAjax;
use Illuminate\Http\Response;
use AdminHelpers\Auth\Concerns\HasResponse;
use AdminHelpers\Auth\Concerns\HasAuthModel;
use AdminHelpers\Auth\Events\UserRegistered;
use AdminHelpers\Auth\Concerns\HasAuthFields;
use AdminHelpers\Auth\Concerns\HasOTPAuthorization;

trait HasRegistration
{
    use HasAuthModel,
        HasAuthFields,
        HasOTPAuthorization,
        HasResponse;

    /**
     * Returns the success message
     *
     * @return string
     */
    public function getSuccessMessage()
    {
        return _('Boli ste úspešne registrovaní!');
    }

    /**
     * Run registration with OTP request process first
     *
     * @return void
     */
    public function registerOTP()
    {
        $model = $this->getAuthModel();

        $data = $this->getValidatedOtpData($model);

        // If error response is present, throw it.
        if ( $data instanceof Response || $data instanceof AutoAjax ) {
            return $data;
        }

        return $this->createRegistrationOtpResponse($model, $data);
    }

    /**
     * Validate registration with existing OTP in request
     *
     * @return void
     */
    public function registerOTPVerify()
    {
        $model = $this->getAuthModel();

        $isNewEntry = $model->exists === false;

        $data = $this->getValidatedRegisterData($model);

        // Verify registration OTP or logged state
        if ( $response = $this->verifyRegistrationOtp($model, $data) ) {
            return $response;
        }

        $model = $this->createUser($model, $data, $isNewEntry);

        if ( $isNewEntry ){
            event(new UserRegistered($model));
        }

        return $this->makeAuthResponse($model, 'register')
                    ->message($this->getSuccessMessage());
    }

    /**
     * Creates verification OTP response for registration process
     *
     * @param  mixed $model
     * @param  mixed $data
     * @return void
     */
    protected function createRegistrationOtpResponse($model, $data)
    {
        $verificator = $this->getVerificator();
        $identifier = $verificator ? ($data[$verificator] ?? null) : null;

        // 1. If no verificator is available
        // 2. Or user is already logged in via social network, and his method is verified after social login (eg. mail)
        if ( !$verificator || $model->isVerified($verificator, $identifier) ) {
            $token = otpModel()->fill([ 'verificator' => $verificator ]);

            return $this->tokenSendResponse($token, true);
        } else {
            $token = $this->createToken($identifier, $verificator, 60)->sendToken();

            return $this->tokenSendResponse($token);
        }
    }

    /**
     * Check if given OTP in request is valid
     *
     * @param  mixed $model
     * @param  mixed $data
     * @param  mixed $destroyToken
     *
     * @return OtpToken|null
     */
    protected function verifyRegistrationOtp($model, $data, $destroyToken = true)
    {
        // No verification method is required, so we can skip verification.
        if (!($verificator = $this->getVerificator())){
            return;
        }

        $identifier = $data[$verificator] ?? '';

        //If user is logged in and verification method is already verified, skip.
        if ( $model->isVerified($verificator, $identifier) === true ) {
            return;
        }

        // Otp code has been passed. Proceed with verification completion.
        if ( $otpCode = request('token') ){
            // If token verification passed, add verified method to model.
            if ( !($token = $this->findToken($otpCode, $identifier)) ) {
                return autoAjax()->error(_('Prihlasovací kód nie je správny.'), 401)->throw();
            }

            $model->addVerified($token->verificator, $identifier);

            // Delete token after verification
            if ( $destroyToken === true ) {
                $token->delete();
            }
        }

        // Otp code has not been passed, so we need to return new code to go through the registration process again.
        else {
            return $this->createRegistrationOtpResponse($model, $data);
        }
    }
}