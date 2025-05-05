<?php

namespace AdminHelpers\Providers;

use Admin\Providers\AdminHelperServiceProvider;

class ConfigServiceProvider extends AdminHelperServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigs(require __DIR__.'/../Config/config.php', 'admin_helpers', []);
    }
}
