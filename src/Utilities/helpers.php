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
