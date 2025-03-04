<?php

namespace AdminHelpers\Providers;

use Admin;
use AdminHelpers\Notifications\Providers\NotificationsServiceProvider;
use AdminHelpers\Auth\Providers\AuthServiceProvider;
use Admin\Providers\AdminHelperServiceProvider;

class AppServiceProvider extends AdminHelperServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        NotificationsServiceProvider::class,
        AuthServiceProvider::class,
    ];

    protected $facades = [];

    protected $routeMiddleware = [];

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