<?php

namespace AdminHelpers\Auth\Controllers;

use AdminHelpers\Auth\Concerns\Authorizable;
use AdminHelpers\Auth\Concerns\HasAuthorization;

class LoginController extends Controller implements Authorizable
{
    use HasAuthorization;

    /**
     * Returns current user response
     *
     * @return void
     */
    public function user()
    {
        return $this->authorizedResponse(admin());
    }
}