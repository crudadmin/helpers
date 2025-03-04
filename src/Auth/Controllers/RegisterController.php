<?php

namespace AdminHelpers\Auth\Controllers;

use AdminHelpers\Auth\Concerns\Authorizable;
use AdminHelpers\Auth\Concerns\HasRegistration;
use Admin;

class RegisterController extends Controller implements Authorizable
{
    use HasRegistration;

    // Which table is used for registration.
    public $table = 'users';

    public function getAuthModel()
    {
        //Get logged user in case of incomplete registration via Google/Apple socials.
        //Or new user.
        return client() ?: Admin::getModelByTable($this->table);
    }

    public function loginResponse($user, $type = null)
    {
        //TODO:
        return autoAjax();
    }
}