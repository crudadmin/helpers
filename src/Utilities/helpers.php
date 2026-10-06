<?php

if ( !function_exists('isTestEnvironment') ) {
    /**
     * Check if the environment is test
     *
     * @return bool
     */
    function isTestEnvironment()
    {
        // "stagging" is kept for projects which used the misspelled environment name
        return app()->environment(['local', 'staging', 'stagging']);
    }
}

if ( !function_exists('bootstrapRequest') ) {
    /**
     * New bootstrap request of the application type (app-type header by default).
     *
     * @param  string|null  $appType
     * @return \Admin\Core\Bootstrap\BootstrapRequest
     */
    function bootstrapRequest($appType = null)
    {
        return \AdminHelpers\Bootstrap\BootstrapResolver::make($appType);
    }
}
