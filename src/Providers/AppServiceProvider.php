<?php

namespace AdminHelpers\Providers;

use Admin;
use Admin\Providers\AdminHelperServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Kernel;

class AppServiceProvider extends AdminHelperServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        EventsServiceProvider::class,
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

        //Boot providers after this provider boot
        $this->registerProviders([]);

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
        $this->publishes([__DIR__ . '/../Config/config.php' => config_path('adminhelpers.php') ], 'adminhelpers.config');
    }
}