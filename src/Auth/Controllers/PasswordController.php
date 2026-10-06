<?php

namespace AdminHelpers\Auth\Controllers;

use AdminHelpers\Auth\Concerns\Authorizable;
use AdminHelpers\Auth\Concerns\HasAuthModel;
use AdminHelpers\Auth\Concerns\HasPasswordReset;
use AdminHelpers\Auth\Concerns\HasResponse;

class PasswordController extends Controller implements Authorizable
{
    use HasAuthModel,
        HasPasswordReset,
        HasResponse;
}
