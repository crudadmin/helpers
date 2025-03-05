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
                (new AuthResponse($user, $type))->toArray()
            );
    }
}