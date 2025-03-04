<?php

namespace AdminHelpers\Auth\Concerns;

trait HasAuthFields
{
    public function getAuthFields()
    {
        // Login by dynamic identifier
        if ( request('identifier') ) {
            return [ 'identifier' => 'required' ];
        }

        $phoneRules = (function_exists('phoneValidatorRule') ? phoneValidatorRule() : '');

        return [
            'phone' => 'required_without:email|'.$phoneRules,
            'email' => 'required_without:phone',
        ];
    }
}