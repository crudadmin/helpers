<?php

namespace AdminHelpers\Auth\Concerns;

use Admin;
use Illuminate\Support\Facades\Hash;
use AdminHelpers\Auth\Concerns\HasResponse;
use AdminHelpers\Auth\Concerns\HasAuthFields;
use AdminHelpers\Auth\Concerns\HasOTPAuthorization;

trait HasAuthorization
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
        return $this->findUserFromRequest(Admin::getAuthModel());
    }

    /**
     * Run login authorization process by username and password
     *
     * @return void
     */
    public function login()
    {
        $this->validate(request(), [
            ...$this->getAuthFields(),
            'password' => 'required',
        ]);

        $user = $this->getAuthModel();

        //User authorized
        if ( $user && Hash::check(request('password'), $user->password) ){
            return $this->makeAuthResponse($user);
        }

        return autoAjax()->error(_('Prihlasovacie údaje nie sú správne.'), 401);
    }

    /**
     * Run login authorization OTP request
     *
     * @return void
     */
    public function loginOTP()
    {
        $this->validate(request(), $this->getAuthFields());

        if ( !($user = $this->getAuthModel()) ) {
            return autoAjax()->error(_('Používateľ s prihlasovácimi údajmi nebol nájdeny.'), 401);
        }

        $token = $this->createToken(
            $user,
            $this->getVerificator($user)
        )->sendToken();

        return $this->tokenSendResponse($token);
    }

    /**
     * Verify authorization OTP request and log in if successful
     *
     * @return void
     */
    public function loginOTPVerify()
    {
        $this->validate(request(), [
            ...$this->getAuthFields(),
            'token' => 'required',
        ]);

        if ( !($user = $this->getAuthModel()) ) {
            return autoAjax()->error(_('Používateľ s prihlasovácimi údajmi nebol nájdeny.'), 401);
        }

        if ( !($token = $this->findToken(request('token'), $user->getAttribute($this->getVerificator($user)))) ){
            return autoAjax()->error(_('Prihlasovací kód nie je správny.'), 401);
        }

        $user->addVerified($token->verificator)->save();

        $token->forceDelete();

        return $this->makeAuthResponse($user);
    }

    /**
     * Run login authorization by socialite token
     *
     * @param string $driverType
     * @return void
     */
    public function loginBySocialiteToken($driverType)
    {
        return (new \Admin\Socialite\SocialAuth($driverType))
            ->onSuccess(function($auth){
                $user = $auth->getUser();

                if ( $user->email ){
                    $user->addVerified('email')->save();
                }

                return $this->makeAuthResponse($user, 'social');
            })
            ->callbackResponse(request('token'));
    }

    /**
     * Run logout process
     *
     * @return void
     */
    public function logout()
    {
        $user = client();

        // Remove access token
        if ( $token = $user->currentAccessToken() ){
            $token->delete();
        }

        // Flush notification tokens
        if ( method_exists($user, 'notificationTokens') ){
            $user->notificationTokens()->where('access_token_id', $token->getKey())->delete();
        }

        return autoAjax()->success(_('Boli ste úspešne odhlasený.'));
    }
}