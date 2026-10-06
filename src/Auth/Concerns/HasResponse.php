<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Utilities\AuthResponse;
use AdminHelpers\Bootstrap\BootstrapRequest;
use AdminHelpers\Bootstrap\BootstrapResolver;
use Illuminate\Auth\Events\Login;
use LogicException;

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
        $guardName = method_exists($this, 'getAuthGuardName') ? $this->getAuthGuardName() : null;

        // A configured guard wins, the guard of the model is the default (admin guard for models
        // without their own $guard, which is wrong for e-shop clients).
        $guard = $guardName ? auth()->guard($guardName) : $user->getGuard();

        $guard->setUser($user);

        // setUser() of a token guard dispatches no Login event. Listeners of the projects and
        // packages (e.g. the e-shop cart merge of the guest cart into the client cart) need it.
        if ( config('admin_helpers.auth.login_event', false) === true ) {
            event(new Login($guardName ?: config('auth.defaults.guard'), $user, false));
        }

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
        if ( config('admin_helpers.auth.response') === 'bootstrap' ) {
            return $this->bootstrapAuthorizedResponse($user, $type);
        }

        return autoAjax()
            ->success($this->authorizedMessage($type))
            ->data(
                (new AuthResponse($user, $type, abilities: $this->tokenAbilities($user)))->toArray()
            );
    }

    /**
     * Message of the authorized response: a login, registration or password reset (with a type)
     * says the user is logged in. GET /user (no type) only reads the logged user, the frontend
     * showed "logged in" on every refresh of the user.
     *
     * @param  string|null $type
     *
     * @return string|null
     */
    protected function authorizedMessage($type = null)
    {
        return $type ? _('Boli ste úspešne prihlásený.') : null;
    }

    /**
     * Authorized response with the authenticated() sections of the bootstrap request of the
     * project (selected by the app-type header), so the client receives the auth section with
     * the new token together with the data of the logged user (e.g. the merged e-shop cart).
     *
     * @param  mixed $user
     * @param  string|null $type
     *
     * @return mixed
     */
    protected function bootstrapAuthorizedResponse($user, $type = null)
    {
        $request = BootstrapResolver::make();

        if ( !($request instanceof BootstrapRequest) ) {
            throw new LogicException('The bootstrap login response needs a bootstrap request extending '.BootstrapRequest::class.'.');
        }

        $request->withClient($user);

        // Without a type (e.g. GET /user) no new token is created, as in the default response
        if ( $type ) {
            $request->setToken($type, $this->tokenAbilities($user));
        }

        // The auth section carries the token, add it when authenticated() of the project does not
        // compose it. A section already composed is skipped by only().
        $store = $request->authenticated() + $request->only(['auth']);

        return autoAjax()
            ->success($this->authorizedMessage($type))
            ->store($store);
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
