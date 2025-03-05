<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Support\Facades\Route;
use AdminHelpers\Auth\Controllers\OTPController;
use AdminHelpers\Auth\Controllers\LoginController;
use AdminHelpers\Auth\Controllers\RegisterController;

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
    public function middleware($callback)
    {
        Route::middleware(['throttle:auth'])->group(function () use ($callback) {
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
