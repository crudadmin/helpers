<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Utilities\AuthResponse;

trait HasResponse
{
    /**
     * Make the authorized response
     *
     * @param  mixed $user
     * @param  string $type
     *
     * @return mixed
     */
    protected function makeAuthResponse($user, $type = 'login')
    {
        $user->getGuard()->setUser($user);

        return $this->authorizedResponse($user, $type);
    }

    /**
     * Returns the success response
     *
     * @param  mixed $user
     * @param  string $type
     *
     * @return mixed
     */
    public function authorizedResponse($user, $type = null)
    {
        return autoAjax()
            ->success(_('Boli ste úspešne prihlásený.'))
            ->data(
                (new AuthResponse($user, $type, abilities: $this->tokenAbilities($user)))->toArray()
            );
    }

    /**
     * Sanctum abilities of the token created on login. Controllers may limit them, e.g. by a request parameter.
     *
     * @param  mixed $user
     *
     * @return array
     */
    protected function tokenAbilities($user)
    {
        return ['*'];
    }
}