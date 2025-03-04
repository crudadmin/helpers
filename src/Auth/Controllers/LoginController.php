<?php

namespace AdminHelpers\Auth\Controllers;

use Illuminate\Routing\Controller;
use AdminHelpers\Auth\Concerns\HasAuthorization;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LoginController extends Controller
{
    use HasAuthorization,
        AuthorizesRequests,
        ValidatesRequests;
}