<?php

namespace AdminHelpers\Auth\Concerns;

use Illuminate\Support\Facades\Hash;
use AdminHelpers\Auth\Concerns\HasAuthFields;
use AdminHelpers\Auth\Concerns\HasOTPAuthorization;
use AdminHelpers\Auth\Concerns\HasResponse;

trait HasAuthorization
{
    use HasAuthFields,
        HasOTPAuthorization,
        HasResponse;

    public function login()
    {
        $this->validate(request(), [
            ...$this->getAuthFields(),
            'password' => 'required',
        ]);

        $user = $this->getAuthModel();

        //User authorized
        if ( $user && $user->password && Hash::check(request('password'), $user->password) ){
            return $this->makeAuthResponse($user);
        }

        return autoAjax()->error(_('Prihlasovacie údaje nie sú správne.'), 401);
    }

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