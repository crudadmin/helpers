<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Support\Facades\Route;
use AdminHelpers\Auth\Controllers\OTPController;
use AdminHelpers\Auth\Controllers\LoginController;
use AdminHelpers\Auth\Controllers\RegisterController;
use AdminHelpers\Auth\Controllers\OAuthController;
use AdminHelpers\Auth\Controllers\IdentifierController;
use AdminHelpers\Auth\Middleware\FixPhoneNumber;

class AdminAuth
{
    /**
     * Login routes with OTP and socialite support
     *
     * @param  $controller
     *
     * @return void
     */
    public function login($controller = LoginController::class)
    {
        $this->middleware(function () use ($controller) {
            Route::post('auth/login', [$controller, 'login']);
            Route::post('auth/login/otp-verify', [$controller, 'loginOTPVerify']);
            Route::post('auth/login/socialite/{driver}', [$controller, 'loginBySocialiteToken']);
        });

        // OTP throttle on sending code
        $this->otpMiddleware(function () use ($controller) {
            Route::post('auth/login/otp', [$controller, 'loginOTP']);
        });
    }

    /**
     * Returns current user
     *
     * @param  mixed $controller
     * @return void
     */
    public function user($controller = LoginController::class)
    {
        $this->middleware(function () use ($controller) {
            Route::get('user', [$controller, 'user']);
        });
    }

    public function oauth($controller = OAuthController::class)
    {
        $this->middleware(function () use ($controller) {
            Route::get('admin/oauth/authorize', [$controller, 'oauthAuthorize']);
            Route::get('admin/oauth/authorize/redirect', [$controller, 'oauthAuthorizeRedirect']);
        }, ['admin']);

        Route::post('admin/oauth/token', [$controller, 'token']);
    }

    /**
     * Logout route
     *
     * @param  $controller
     *
     * @return void
     */
    public function logout($controller = LoginController::class)
    {
        Route::any('auth/logout', [$controller, 'logout']);
    }

    /**
     * OTP routes
     *
     * @param  $controller
     *
     * @return void
     */
    public function otp($controller = OTPController::class)
    {
        $this->middleware(function () use ($controller) {
            Route::post('auth/otp/verify', [$controller, 'verify']);
        });

        // OTP throttle on sending code
        $this->otpMiddleware(function () use ($controller) {
            Route::post('auth/otp/resend', [$controller, 'resend']);
        });
    }

    /**
     * Register routes
     *
     * @param  $controller
     *
     * @return void
     */
    public function register($controller = RegisterController::class)
    {
        $this->middleware(function () use ($controller) {
            Route::post('auth/register/otp-verify', [$controller, 'registerOTPVerify']);
        });

        // OTP throttle on sending code
        $this->otpMiddleware(function () use ($controller) {
            Route::post('auth/register/otp', [$controller, 'registerOTP']);
        });
    }

    /**
     * Change of identifier (email / phone) routes with OTP verification.
     * Must be registered inside an authenticated route group.
     *
     * @param  $controller
     *
     * @return void
     */
    public function changeIdentifier($controller = IdentifierController::class)
    {
        $this->middleware(function () use ($controller) {
            Route::post('auth/identifier/verify', [$controller, 'verifyOtp']);
        });

        // OTP throttle on sending code. Verificator (email/phone) is passed in the
        // request, phone numbers are normalized into the international format.
        $this->otpMiddleware(function () use ($controller) {
            Route::post('auth/identifier/otp', [$controller, 'sendOtp'])->middleware(FixPhoneNumber::class);
        });
    }

    /**
     * Middleware for throttling
     *
     * @param  $callback
     *
     * @return void
     */
    public function middleware($callback, $groups = [])
    {
        return Route::middleware(['throttle:auth', ...$groups])->group(function ($a) use ($callback) {
            $callback();
        });
    }

    /**
     * Middleware for throttling OTP
     *
     * @param  $callback
     *
     * @return void
     */
    public function otpMiddleware($callback)
    {
        Route::middleware(['throttle:otp'])->group(function () use ($callback) {
            $callback();
        });
    }
}