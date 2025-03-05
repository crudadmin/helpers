<?php

if ( !function_exists('isTestEnvironment') ) {
    /**
     * Check if the environment is test
     *
     * @return bool
     */
    function isTestEnvironment()
    {
        return app()->environment(['local', 'stagging']);
    }
}
