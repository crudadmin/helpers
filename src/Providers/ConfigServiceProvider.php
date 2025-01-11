<?php

namespace AdminHelpers\Providers;

use Admin\Providers\AdminHelperServiceProvider;

class ConfigServiceProvider extends AdminHelperServiceProvider
{
    private $packageConfigKey = 'AdminHelpers';

    private function getPackageConfigPath()
    {
        return __DIR__.'/../Config/config.php';
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->getPackageConfigPath(), $this->packageConfigKey
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Merge crudadmin configs
        $this->mergeAdminConfigs(require __DIR__.'/../Config/admin.php');

        //Merge AdminHelpers configs
        $this->mergeConfigs(
            require $this->getPackageConfigPath(),
            $this->packageConfigKey,
            [],
            [],
        );
    }
}
