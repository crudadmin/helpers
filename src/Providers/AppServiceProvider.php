<?php

namespace AdminHelpers\Providers;

use Admin;
use Admin\Providers\AdminHelperServiceProvider;
use AdminHelpers\Auth\Providers\AuthServiceProvider;
use AdminHelpers\Shared\Middleware\AuthOptionalMiddleware;
use AdminHelpers\Notifications\Providers\NotificationsServiceProvider;

class AppServiceProvider extends AdminHelperServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        NotificationsServiceProvider::class,
        AuthServiceProvider::class,
        SessionServiceProvider::class,
    ];

    protected $facades = [];

    protected $routeMiddleware = [
        'auth.optional' => AuthOptionalMiddleware::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerModels();

        $this->commands([]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();

        $this->registerProviders();

        $this->bootRouteMiddleware();

        $this->addPublishes();

        require_once __DIR__ . '/../Utilities/helpers.php';
    }

    private function registerModels()
    {
        Admin::registerAdminModels(__dir__ . '/../Models/**', 'AdminHelpers\Models');
    }

    private function addPublishes()
    {
        $this->publishes([__DIR__ . '/../Config/config.php' => config_path('admin_helpers.php') ], 'admin_helpers.config');
    }
}