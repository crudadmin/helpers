<?php

namespace AdminHelpers\Auth\Providers;

use Admin;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Admin\Providers\AdminHelperServiceProvider;

class AuthServiceProvider extends AdminHelperServiceProvider
{
    protected $facades = [
        'AdminAuth' => [
            'facade' => \AdminHelpers\Auth\Facades\AdminAuth::class,
            'class' => ['admin.auth', \AdminHelpers\Auth\Utilities\AdminAuth::class],
        ],
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();

        require __DIR__.'/../auth.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ( hasOtpEnabled() ) {
            Admin::registerAdminModels(__dir__ . '/../Models/Otp/**', 'AdminHelpers\Auth\Models\Otp');
        }

        $this->commands([
            \AdminHelpers\Auth\Commands\CleanOtpTokens::class,
        ]);

        $this->loadViewsFrom(__DIR__ . '/../Views', 'admin_helpers');

        $this->setThrottleLimiters();
    }

    private function setThrottleLimiters()
    {
        RateLimiter::for('otp', function (Request $request) {
            return [
                Limit::perMinute(config('admin_helpers.auth.throttle.otp'))->by(($request->user()?->id ?: $request->ip()).'_'.$request->getRequestUri()),
            ];
        });

        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(config('admin_helpers.auth.throttle.auth'))->by(($request->user()?->id ?: $request->ip()).'_'.$request->getRequestUri()),
            ];
        });
    }
}