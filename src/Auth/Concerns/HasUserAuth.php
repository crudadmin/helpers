<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Concerns\HasPhoneFormat;
use Laravel\Sanctum\HasApiTokens;

trait HasUserAuth
{
    use HasPhoneFormat,
        HasApiTokens;

    public function getGuard()
    {
        return auth()->guard($this->guard);
    }

    public function scopeFindFromRequest($query)
    {
        $email = request('email');
        $phone = request('phone');
        $identifier = request('identifier');
        $rowId = request('row_id');

        return $query->loginBy($email, $phone, $identifier, $rowId);
    }

    public function scopeLoginBy($query, $email, $phone, $identifier, $rowId = null)
    {
        // When verificator row id is present, we want to find user by that row id.
        if ( $rowId ) {
            $query->where($this->qualifyColumn('id'), $rowId);
        }

        //Search by any
        if ( $identifier ) {
            $query->where(function($query) use ($identifier) {
                $query->where($query->qualifyColumn('email'), $identifier)
                      ->orWhere($query->qualifyColumn('phone'), $this->toPhoneFormat($identifier));
            });
        }

        //Search by email
        else if ( $email ){
            $query->where($query->qualifyColumn('email'), $email);
        }

        //Search by phone
        else if ( $phone ) {
            $query->where($query->qualifyColumn('phone'), $this->toPhoneFormat($phone));
        }

        //Search by none
        else {
            $query->where($query->qualifyColumn('id'), 0);
        }
    }

    public function createLoginResponse($tokenType = false)
    {
        $data = [
            'driver' => $this->getTable(),
            'user' => $this->setUserResponse(),
            'device_tokens' => $this->notificationTokens()->pluck('token'),
        ];

        //We does not want create token if false has been given
        if ( $tokenType ) {
            $token = $this->createToken($tokenType ?: 'default');

            $data['token'] = [
                'token' => $token->plainTextToken,
                'expiration' => null,
            ];
        }

        return $data;
    }

    public function addVerified($method)
    {
        $verified = $this->verified?->toArray() ?: [];

        if ( $method && in_array($method, $verified) == false ) {
            $this->verified = array_merge($verified, [ $method ]);
        }

        return $this;
    }

    public function isVerified($method)
    {
        //If not verification method has been passed, and user exists. We can pass true.
        if ( $this->exists && !$method ){
            return true;
        }

        return in_array($method, $this->verified?->toArray() ?: []);
    }
}
