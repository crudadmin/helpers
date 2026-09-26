<?php

namespace AdminHelpers\Importer\Providers;

use Admin\Providers\AdminPackageServiceProvider;

/**
 * Importer module, enabled by admin_helpers.importer.enabled.
 */
class ImporterServiceProvider extends AdminPackageServiceProvider
{
    protected $models = [
        __DIR__ . '/../Models/**' => 'AdminHelpers\Importer\Models',
    ];

    private function isEnabled()
    {
        return config('admin_helpers.importer.enabled') === true;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ( $this->isEnabled() === false ) {
            return;
        }

        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ( $this->isEnabled() === false ) {
            return;
        }

        parent::boot();
    }
}
