<?php

function hasOtpEnabled()
{
    return config('admin_helpers.auth.otp.enabled', false) === true;
}

function otpModel()
{
    return Admin::getModelByTable('otp_tokens');
}