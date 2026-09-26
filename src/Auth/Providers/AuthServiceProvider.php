<?php

namespace AdminHelpers\Auth\Providers;

use Admin\Providers\AdminPackageServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthServiceProvider extends AdminPackageServiceProvider
{
    /**
     * Blade views of the package, namespace => directory.
     */
    protected $views = [
        'admin_helpers' => __DIR__.'/../Views',
    ];

    protected $facades = [
        'AdminAuth' => [
            'facade' => \AdminHelpers\Auth\Facades\AdminAuth::class,
            'class' => ['admin.auth', \AdminHelpers\Auth\Utilities\AdminAuth::class],
        ],
    ];

    protected $commands = [
        \AdminHelpers\Auth\Commands\CleanOtpTokens::class,
        \AdminHelpers\Auth\Commands\FixOtpVerifiedMethods::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        require_once __DIR__.'/../auth.php';
    }

    /**
     * OTP models are registered only when the OTP authorization is enabled.
     *
     * @return array<string, string>
     */
    protected function models()
    {
        return hasOtpEnabled()
            ? [__DIR__.'/../Models/Otp/**' => 'AdminHelpers\Auth\Models\Otp']
            : [];
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

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
