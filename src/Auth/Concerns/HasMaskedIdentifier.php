<?php

namespace AdminHelpers\Auth\Concerns;

trait HasMaskedIdentifier
{
    /**
     * Returns the identifier with the middle part replaced by asterisks,
     * so the account's real e-mail/phone is not leaked to the requester.
     *
     * @return string|null
     */
    public function getMaskedIdentifier()
    {
        $identifier = $this->identifier;

        if ( !$identifier ) {
            return $identifier;
        }

        // E-mail: mask the middle of the local part and of the domain
        if ( $this->verificator == 'email' && str_contains($identifier, '@') ) {
            [$local, $domain] = explode('@', $identifier, 2);

            return $this->maskMiddle($local, 1, 1).'@'.$this->maskMiddle($domain, 1, 1);
        }

        // Phone / other: keep the beginning and the end, mask the middle
        return $this->maskMiddle($identifier, 4, 2);
    }

    /**
     * Keeps the first/last characters and replaces the middle with asterisks.
     *
     * @param  string  $value
     * @param  int  $keepStart
     * @param  int  $keepEnd
     * @return string
     */
    protected function maskMiddle($value, $keepStart, $keepEnd)
    {
        $length = mb_strlen($value);

        // Not enough characters to keep both edges -> mask everything
        if ( $length <= ($keepStart + $keepEnd) ) {
            return str_repeat('*', max($length, 1));
        }

        return mb_substr($value, 0, $keepStart)
                .str_repeat('*', $length - $keepStart - $keepEnd)
                .mb_substr($value, -$keepEnd);
    }
}
