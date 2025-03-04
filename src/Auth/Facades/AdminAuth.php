<?php
namespace AdminHelpers\Auth\Facades;

use Illuminate\Support\Facades\Facade;

class AdminAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'admin.auth';
    }
}