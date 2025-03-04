<?php

namespace AdminHelpers\Auth\Controllers;

use AdminHelpers\Auth\Concerns\HasOTPAuthorization;

class OTPController extends Controller
{
    use HasOTPAuthorization;
}