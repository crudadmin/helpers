<?php

namespace AdminHelpers\Auth\Models\Otp;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Admin;

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

    protected $settings = [
        'columns.active_till.name' => 'Aktívny do',
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
        ];
    }

    public function setAdminRowsResponse()
    {
        $this->append([
            'active_till',
        ])->makeVisible(['active_till']);
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
        $query->where('created_at', '>=', now()->subMinutes(self::MINUTES_ACTIVE));
    }

    public function scopeUnactive($query)
    {
        $query->where('created_at', '<', now()->subMinutes(self::MINUTES_ACTIVE));
    }

    public function getIsActiveAttribute()
    {
        return $this->created_at >= now()->subMinutes(self::MINUTES_ACTIVE);
    }

    public function getActiveTillAttribute()
    {
        return $this->created_at->addMinutes(self::MINUTES_ACTIVE)->format('d.m.Y H:i');
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