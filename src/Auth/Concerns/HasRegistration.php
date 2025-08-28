<?php

namespace AdminHelpers\Auth\Concerns;

use Admin;
use AutoAjax\AutoAjax;
use Illuminate\Http\Response;
use AdminHelpers\Auth\Concerns\HasResponse;
use AdminHelpers\Auth\Events\UserRegistered;
use AdminHelpers\Auth\Concerns\HasAuthFields;
use AdminHelpers\Auth\Concerns\HasOTPAuthorization;

trait HasRegistration
{
    use HasAuthFields,
        HasOTPAuthorization,
        HasResponse;

    /**
     * Returns the auth model into which we are logging in
     *
     * @return AdminModel|null
     */
    public function getAuthModel()
    {
        //Get logged user in case of incomplete registration via Google/Apple socials.
        if ( $client = client() ) {
            return $client;
        }

        if ( $this->table ) {
            return Admin::getModelByTable($this->table);
        }

        // Get default user provider.
        return Admin::getModel(class_basename(env('AUTH_MODEL', config('auth.providers.users.model'))));
    }

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
        $this->verifyRegistrationOtp($model);

        $model = $this->createUser($model, $data, $isNewEntry);

        if ( $isNewEntry ){
            event(new UserRegistered($model));
        }

        return $this->makeAuthResponse($model, 'register')
                    ->message($this->getSuccessMessage());
    }

    /**
     * Check if given OTP in request is valid
     *
     * @param  mixed $model
     * @param  mixed $destroyToken
     *
     * @return OtpToken|null
     */
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