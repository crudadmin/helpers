<?php

namespace AdminHelpers\Auth\Concerns;

use Admin\Eloquent\AdminModel;
use Illuminate\Database\Eloquent\Builder;
use AdminHelpers\Auth\Concerns\HasPhoneFormat;

trait HasAuthFields
{
    use HasPhoneFormat;

    /**
     * Get avilable authorization fields for incoming request
     *
     * @return array
     */
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

    /**
     * findUserFromRequest
     *
     * @param  mixed $query
     * @return void
     */
    public function findUserFromRequest(AdminModel|Builder|string $query)
    {
        $query = $this->getAuthQuery($query);

        $email = request('email');
        $phone = request('phone');
        $identifier = request('identifier');
        $rowId = request('row_id');

        $query = $this->findUserByAuthFields($query, $email, $phone, $identifier, $rowId);

        return $query->first();
    }

    /**
     * Casts given query parameter into authorization method
     *
     * @param  mixed $query
     * @return void
     */
    private function getAuthQuery($query)
    {
        if ( is_string($query) || $query instanceof AdminModel ) {
            return $query::query();
        } else if ( $query instanceof Builder ) {
            return $query;
        }
    }

    /**
     * Find user by available authorization fields
     *
     * @param  mixed $query
     *
     * @return Builder
     */
    private function findUserByAuthFields($query, $email, $phone, $identifier, $rowId = null)
    {
        // Fix query instance if model has been given. (redudancy check to avoid wrong result)
        if ( $query instanceof AdminModel ) {
            $query = $query::query();
        }

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

        return $query;
    }
}