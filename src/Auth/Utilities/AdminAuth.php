<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Support\Facades\Route;
use AdminHelpers\Auth\Controllers\OTPController;
use AdminHelpers\Auth\Controllers\LoginController;
use AdminHelpers\Auth\Controllers\RegisterController;
use AdminHelpers\Auth\Controllers\OauthController;

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
        }, ['admin']);
    }

    public function oauth($controller = OauthController::class)
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