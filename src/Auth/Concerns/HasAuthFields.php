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
     * Find user by available authorization fields which their casts
     *
     * @return array
     */
    public function getRequestCasts()
    {
        return [
            'email' => fn($value) => $value,
            'phone' => fn($value) => $this->toPhoneFormat($value),
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

        $availableFields = array_unique(array_merge(['identifier', 'row_id'], array_keys($this->getAuthFields())));

        $params = request()->only($availableFields);

        $query = $this->findUserByAuthFields($query, $params);

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
    private function findUserByAuthFields($query, $params)
    {
        // Fix query instance if model has been given. (redudancy check to avoid wrong result)
        if ( $query instanceof AdminModel ) {
            $query = $query::query();
        }

        // When verificator row id is present, we want to find user by that row id.
        if ( $rowId = ($params['row_id'] ?? null) ) {
            $query->where($query->qualifyColumn('id'), $rowId);
        }

        //Search by any fields defined in $searchBy array dynamically
        if ( ($identifier = ($params['identifier'] ?? null)) && count($this->getModelCasts($query)) > 0 ) {
            $this->findUserByIdentifier($query, $identifier);
        }

        else {
            $this->findUserByParams($query, $params);
        }


        return $query;
    }

    /**
     * Find user by identifier
     *
     * @param  mixed $query
     * @param  string $identifier
     *
     * @return void
     */
    private function findUserByIdentifier($query, $identifier)
    {
        $query->where(function($query) use ($identifier) {
            // Find by dynamically defined fields
            foreach ( $this->getModelCasts($query) as $key => $callback ) {
                $query->orWhere($query->qualifyColumn($key), $callback($identifier));
            }
        });
    }

    /**
     * findUserByParams
     *
     * @param  mixed $query
     * @param  mixed $params
     * @return void
     */
    private function findUserByParams($query, $params)
    {
        foreach ( $this->getModelCasts($query) as $key => $callback ) {
            if ( ($value = $params[$key] ?? null) ) {
                $query->where($query->qualifyColumn($key), $callback($value));

                return $query;
            }
        }

        //Search by none, if no valid params has been passed into request.
        $query->where($query->qualifyColumn('id'), 0);
    }

    /**
     * getModelCasts
     *
     * @return array
     */
    private function getModelCasts($query)
    {
        $model = $query->getModel();
        return array_filter($this->getRequestCasts(), function($key) use ($model) {
            return $model->getField($key);
        }, ARRAY_FILTER_USE_KEY);
    }
}