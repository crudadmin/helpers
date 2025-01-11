<?php

namespace AdminHelpers\Notifications\Models;

use AdminHelpers\Notifications\Admin\Buttons\EnableDebugNotificationToken;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class NotificationsToken extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-11-28 17:31:15';

    /*
     * Template name
     */
    protected $name = 'Notifikačné tokeny';

    protected $active = false;

    protected $publishable = false;

    protected $sortable = false;

    protected $insertable = false;

    protected $group = 'settings';

    protected $icon = 'fa-send';

    protected $buttons = [
        EnableDebugNotificationToken::class,
    ];

    public $timestamps = false;

    public function active()
    {
        return hasDebugFeature();
    }

    /*
     * Automatic form and database generator by fields list (prettier-ignore)
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'app' => 'name:App|type:select|options:'.implode(',', config('admin_helpers.notifications.apps')).'|enum|required',
            'platform' => 'name:Platforma|type:select|options:'.implode(',', config('admin_helpers.notifications.platforms')).'|enum|required',
            'table' => 'name:Tabuľka|index:row_id|type:select|options:'.implode(',', config('admin_helpers.notifications.recipients_tables')).'|enum',
            'row_id' => 'name:Záznam|type:integer|max:0',
            'access_token_id' => 'name:Access token|type:integer|min:0|inaccessible',
            'token' => 'name:Token',
            'state' => 'name:Stav tokenu|type:select|options:ok,invalid,unknown|enum|index|default:ok',
            'debug' => 'name:Debug zapnutý|type:checkbox|default:0|index',
            'created_at' => 'name:Datum vytvorenia|type:datetime|default:CURRENT_TIMESTAMP|required|column_visible'
        ];
    }

    public function options()
    {
        return [
            'state' => [
                'ok' => _('Funkčný token'),
                'unknown' => _('Neznáme zariadenie'),
                'invalid' => _('Neaktívne zariadenie'),
            ],
        ];
    }

    public function getFilterStates()
    {
        $school = admin()?->school;

        $presentStages = $school ? $school->getStudentStagesAvailable() : null;

        return array_filter([
            [
                'name' => _('DEV Vypnutý'),
                'title' => _('Vypnuté odosielanie na DEV prostredí'),
                'color' => 'red',
                'active' => function(){
                    return !$this->debug;
                },
                'query' => function($query){
                    return $query->where('debug', false);
                },
            ],
            [
                'name' => _('DEV Zapnutý'),
                'title' => _('Zapnuté odosielanie na DEV prostredí'),
                'color' => 'green',
                'active' => function(){
                    return $this->debug;
                },
                'query' => function($query){
                    return $query->where('debug', true);
                },
            ],
        ]);
    }
}