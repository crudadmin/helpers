<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Utilities\PasswordReset;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

trait HasPasswordReset
{
    /**
     * Sends the password reset link. The response is the same whether the account exists or not,
     * so the endpoint can not be used to find registered e-mails.
     *
     * @return \AutoAjax\AutoAjax
     */
    public function forgotPassword()
    {
        $this->validate(request(), [
            'email' => 'required|email',
        ]);

        PasswordReset::broker($this->getAuthGuardName())->sendResetLink(
            request()->only('email')
        );

        return autoAjax()->success(_('Ak účet s týmto e-mailom existuje, poslali sme naň odkaz na nastavenie hesla.'));
    }

    /**
     * Sets the new password by the token of the link (reset or first setting of the password)
     * and logs the user in.
     *
     * @return mixed
     */
    public function resetPassword()
    {
        $this->validate(request(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => $this->passwordRules(),
        ]);

        $resetUser = null;

        $status = PasswordReset::broker($this->getAuthGuardName())->reset(
            request()->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use (&$resetUser) {
                // Plain password, the password setter of CrudAdmin hashes it
                $user->password = $password;

                $user->setRememberToken(Str::random(60));

                // The link was delivered to the e-mail, it is verified now
                if ( method_exists($user, 'addVerified') ) {
                    $user->addVerified('email', $user->email);
                }

                $user->save();

                event(new PasswordResetEvent($user));

                $resetUser = $user;
            }
        );

        if ( $status !== Password::PASSWORD_RESET || ! $resetUser ) {
            return autoAjax()->error(_('Odkaz na nastavenie hesla je neplatný alebo mu vypršala platnosť.'), 422);
        }

        return $this->makeAuthResponse($resetUser, 'password')
                    ->message(_('Heslo bolo úspešne nastavené.'));
    }

    /**
     * Validation rules of the new password. The maximum keeps it below 60 characters, a value of
     * 60 characters is stored by the CrudAdmin password setter as an existing hash.
     *
     * @return array
     */
    protected function passwordRules()
    {
        return ['required', 'confirmed', 'min:6', 'max:40'];
    }
}
