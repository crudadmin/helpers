<?php

namespace AdminHelpers\Auth\Controllers;

use AdminHelpers\Auth\Concerns\Authorizable;
use AdminHelpers\Auth\Concerns\HasRegistration;

class RegisterController extends Controller implements Authorizable
{
    use HasRegistration;

    // Which table is used for registration.
    public $table = 'users';
}