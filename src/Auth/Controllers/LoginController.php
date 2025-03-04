<?php

namespace AdminHelpers\Auth\Controllers;

use AdminHelpers\Auth\Concerns\HasAuthorization;

class LoginController extends Controller
{
    use HasAuthorization;
}