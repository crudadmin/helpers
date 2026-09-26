<?php

namespace AdminHelpers\Providers;

use Admin\Providers\AdminPackageServiceProvider;
use AdminHelpers\Auth\Providers\AuthServiceProvider;
use AdminHelpers\Importer\Providers\ImporterServiceProvider;
use AdminHelpers\Notifications\Providers\NotificationsServiceProvider;
use AdminHelpers\Shared\Middleware\AuthOptionalMiddleware;
use AdminHelpers\Sms\SmsServiceProvider;

/**
 * Main provider of the package. Feature modules (notifications, auth, importer...) have their
 * own providers, enabled by the config of the package.
 */
class AppServiceProvider extends AdminPackageServiceProvider
{
    /**
     * Config of the package, config('admin_helpers'). Keys missing in the published config
     * of the project are added from it.
     */
    protected $config = [
        'admin_helpers' => __DIR__.'/../Config/config.php',
    ];

    /**
     * Files which projects can publish, tag => [source => target in the project].
     */
    protected $publishable = [
        'admin_helpers.config' => [
            __DIR__.'/../Config/config.php' => 'config/admin_helpers.php',
        ],
    ];

    protected $providers = [
        NotificationsServiceProvider::class,
        AuthServiceProvider::class,
        SessionServiceProvider::class,
        ImporterServiceProvider::class,
        SmsServiceProvider::class,
    ];

    protected $routeMiddleware = [
        'auth.optional' => AuthOptionalMiddleware::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        require_once __DIR__ . '/../Utilities/helpers.php';
    }

}
