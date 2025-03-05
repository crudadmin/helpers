<?php

namespace AdminHelpers\Auth\Models\Otp;

use Admin;
use Admin\Eloquent\AdminModel;
use AdminHelpers\Auth\Rules\OnWhitelistedTokenCreated;

class WhitelistedToken extends AdminModel
{
    const MINUTES_ACTIVE = 15;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-09-11 09:28:38';

    /*
     * Template name
     */
    protected $name = 'Povolené OTP emaily';

    /*
     * Template title
     */
    protected $title = 'Toto nastavenie vyradi OTP prihlásenie pre vybraných používateľov na 15 minút.';

    /*
     * Model Parent
     * Eg. Article::class
     */
    protected $belongsToModel = null;

    protected $group = 'settings';

    protected $icon = 'fa-lock';

    protected $sortable = false;

    protected $publishable = false;

    protected $rules = [
        OnWhitelistedTokenCreated::class,
    ];

    /*
     * Automatic form and database generator by fields list (prettier-ignore)
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'identifier' => 'name:Email / Tel. číslo|required',
            'valid_to' => 'name:Platné do|type:timestamp|title:Pri prázdnej hodnote bude platné do '.(self::MINUTES_ACTIVE).' minút',
        ];
    }

    public static function getParsedIdentifiers()
    {
        return Admin::cache('otp.whitelisted', function() {
            $identifiers = (new static)->onlyActive()->pluck('identifier');

            return $identifiers->unique()->filter()->values()->toArray();
        });
    }

    public function scopeOnlyActive($query)
    {
        $query->where('valid_to', '>=', now());
    }

    public function scopeUnactive($query)
    {
        $query->where('valid_to', '<', now());
    }

    public function getIsActiveAttribute()
    {
        return $this->valid_to >= now();
    }

    public function getFilterStates()
    {
        return [
            [
                'name' => _('Aktívne'),
                'color' => 'green',
                'active' => function(){
                    return $this->isActive;
                },
                'query' => function($query){
                    return $query->onlyActive();
                },
            ],
            [
                'name' => _('Neaktívne'),
                'color' => 'red',
                'active' => function(){
                    return !$this->isActive;
                },
                'query' => function($query){
                    return $query->unactive();
                },
            ],
        ];
    }
}