<?php

namespace AdminHelpers\Auth\Utilities;

use AdminHelpers\Auth\Notifications\SetPasswordNotification;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;
use LogicException;

/**
 * Password reset links of the auth module, for any guard (users, e-shop clients...).
 *
 * The broker is admin_helpers.auth.password.broker, or the user provider of the guard. A missing
 * auth.passwords entry of that provider is filled with the Laravel defaults
 * (password_reset_tokens table), so projects need no extra config.
 */
class PasswordReset
{
    /**
     * Password broker of the guard.
     *
     * @param  string|null  $guard
     * @return PasswordBroker
     */
    public static function broker($guard = null)
    {
        $name = static::brokerName($guard);

        if ( ! config('auth.passwords.'.$name) ) {
            config()->set('auth.passwords.'.$name, [
                'provider' => $name,
                'table' => config('auth.passwords.users.table', 'password_reset_tokens'),
                'expire' => config('admin_helpers.auth.password.expire', 60),
                'throttle' => 60,
            ]);
        }

        return Password::broker($name);
    }

    /**
     * Name of the password broker, the provider of the guard by default.
     *
     * @param  string|null  $guard
     * @return string
     */
    public static function brokerName($guard = null)
    {
        if ( $broker = config('admin_helpers.auth.password.broker') ) {
            return $broker;
        }

        $guard = $guard ?: config('admin_helpers.auth.guard') ?: config('auth.defaults.guard');

        if ( ! ($provider = config('auth.guards.'.$guard.'.provider')) ) {
            throw new LogicException('Guard ['.$guard.'] has no user provider for password resets.');
        }

        return $provider;
    }

    /**
     * Link of the client application where the user sets a new password.
     *
     * The model may define getResetLink($token) (used by the CrudAdmin reset e-mail too),
     * otherwise admin_helpers.auth.password.reset_url with {token} and {email} placeholders.
     *
     * @param  mixed  $user
     * @param  string  $token
     * @return string
     */
    public static function url($user, $token)
    {
        if ( method_exists($user, 'getResetLink') ) {
            return $user->getResetLink($token);
        }

        if ( ! ($url = config('admin_helpers.auth.password.reset_url')) ) {
            throw new LogicException('Set admin_helpers.auth.password.reset_url or getResetLink() of the model.');
        }

        return strtr($url, [
            '{token}' => urlencode($token),
            '{email}' => urlencode((string) $user->getEmailForPasswordReset()),
        ]);
    }

    /**
     * Sends a link to set the password, e.g. to a client registered during an order without
     * a password. A random password is never sent by e-mail.
     *
     * @param  mixed  $user
     * @param  string|null  $guard
     * @return string  the token, for logs or custom notifications
     */
    public static function sendSetPasswordLink($user, $guard = null)
    {
        $token = static::broker($guard)->createToken($user);

        $user->notify(new SetPasswordNotification($token, static::url($user, $token)));

        return $token;
    }
}
