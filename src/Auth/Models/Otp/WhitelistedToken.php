<?php

namespace AdminHelpers\Auth\Models\Otp;

use Admin;
use Admin\Eloquent\AdminModel;
use AdminHelpers\Auth\Rules\OnWhitelistedTokenCreated;
use Illuminate\Support\Carbon;

class WhitelistedToken extends AdminModel
{
    const MINUTES_ACTIVE = 15;

    // Model created date, for ordering tables in database and in user interface
    protected $migration_date = '2024-09-11 09:28:38';

    // Template name
    protected $name = 'Povolené OTP emaily';

    // Template title
    protected $title = 'Toto nastavenie vyradi OTP prihlásenie pre vybraných používateľov na 15 minút.';

    /*
     * Model Parent
     * Eg. Article::class
     */
    protected $belongsToModel = null;

    protected $group = 'settings';

    protected $settings = [
        'icon' => 'fa-lock',
    ];

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

    /**
     * Identifiers whose whitelist entry is valid right now.
     *
     * Active entries are loaded once per operation (Admin::cache is flushed before every request
     * and queue job), but their validity is checked on every call. An entry which expires during
     * the operation stops being a test identifier at once, so no OTP code is returned in plain
     * text for it.
     *
     * @return array<int, string>
     */
    public static function getParsedIdentifiers()
    {
        $now = now();

        return collect(static::getActiveEntries())
            ->filter(fn ($entry) => $entry['valid_to']->gte($now))
            ->pluck('identifier')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Whitelist entries valid at the time of the first call in this operation.
     *
     * @return array<int, array{identifier: string, valid_to: \Carbon\CarbonInterface}>
     */
    protected static function getActiveEntries()
    {
        return Admin::cache('otp.whitelisted', function () {
            return (new static)->onlyActive()->get(['identifier', 'valid_to'])
                ->filter(fn ($row) => $row->identifier && $row->valid_to)
                ->map(fn ($row) => [
                    'identifier' => $row->identifier,
                    'valid_to' => Carbon::parse($row->valid_to),
                ])
                ->values()
                ->toArray();
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
                'active' => function () {
                    return $this->isActive;
                },
                'query' => function ($query) {
                    return $query->onlyActive();
                },
            ],
            [
                'name' => _('Neaktívne'),
                'color' => 'red',
                'active' => function () {
                    return ! $this->isActive;
                },
                'query' => function ($query) {
                    return $query->unactive();
                },
            ],
        ];
    }
}
