<?php

namespace AdminHelpers\Auth\Rules;

use Admin\Eloquent\AdminRule;

class OnWhitelistedTokenCreated extends AdminRule
{
    public function creating($row)
    {
        $row->valid_to = now()->addMinutes($row::MINUTES_ACTIVE);
    }
}