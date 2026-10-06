<?php

namespace AdminHelpers\Auth\Concerns;

/**
 * Model trait: the CrudAdmin password reset e-mail links to the client application
 * (admin_helpers.auth.password.reset_url) instead of the administration.
 */
trait HasPasswordResetLink
{
    /**
     * Link of the client application setting a new password.
     *
     * @param  string  $token
     * @return string
     */
    public function getResetLink($token)
    {
        $url = config('admin_helpers.auth.password.reset_url');

        return strtr((string) $url, [
            '{token}' => urlencode($token),
            '{email}' => urlencode((string) $this->getEmailForPasswordReset()),
        ]);
    }
}
