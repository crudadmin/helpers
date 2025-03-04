<?php

namespace AdminHelpers\Auth\Concerns;

use Exception;
use Throwable;
use Propaganistas\LaravelPhone\PhoneNumber;

trait HasPhoneFormat
{
    public function toPhoneFormat($number)
    {
        $number = str_replace(' ', '', $number);

        try {
            $phone = new PhoneNumber($number, phoneValidatorSupportedCountries());

            return $phone->formatE164();
        } catch (Throwable $e){
            return $number;
        } catch (Exception $e){
            return $number;
        }
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = $this->toPhoneFormat($value);
    }
}