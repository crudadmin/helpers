<?php

namespace AdminHelpers\Tests;

use Admin\Core\Providers\AppServiceProvider as CoreServiceProvider;
use Admin\Providers\AppServiceProvider as AdminServiceProvider;
use Admin\Resources\Providers\AppServiceProvider as ResourcesServiceProvider;
use AdminHelpers\Providers\AppServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Providers in the order of package discovery in applications.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AdminServiceProvider::class,
            ResourcesServiceProvider::class,
            CoreServiceProvider::class,
            AppServiceProvider::class,
        ];
    }
}
