<?php

namespace AdminHelpers\Importer\Models;

use Admin\Fields\Group;
use Admin\Eloquent\AdminModel;
use AdminHelpers\Importer\Rules\ImportFileRule;
use AdminHelpers\Importer\Concerns\HasImportLogs;
use AdminHelpers\Importer\Buttons\ImportLogButton;
use AdminHelpers\Importer\Concerns\HasImportSupport;
use AdminHelpers\Importer\Buttons\ProcessImportButton;

class ImportsFile extends AdminModel
{
    use HasImportSupport, HasImportLogs;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-12-04 17:08:02';

    /*
     * Template name
     */
    protected $name = 'Importné súbory';

    /*
     * Template title
     */
    protected $title = '';

    protected $icon = 'fa-upload';

    protected $publishable = false;

    protected $sortable = false;

    protected $settings = [
        'increments' => false,
        'grid' => ['big', 'half', 'full'],
        'buttons.create' => 'Nový import',
        'title.insert' => 'Nahrajte importný súbor',
        'title.update' => ':number - :name',
        'columns.number.before' => 'user_id',
        'columns.last_import.name' => 'Posledný import',
    ];

    protected $rules = [
        ImportFileRule::class,
    ];

    protected $buttons = [
        ImportLogButton::class,
        ProcessImportButton::class,
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
            'user' => 'name:Používateľ|belongsTo:admins,username|removeFromForm',
            'name' => 'name:Popis',
            'type' => 'name:Typ importu|type:select|option::name|default:'.($this->getImportClassNameTypes()[0] ?? '').'|required|sub_component:ShowSampleImportFile',
            'file' => 'name:Importny súbor (.xls/.csv)|type:file|extensions:'.getImportExtensions().'|required',
            'state' => 'name:Stav|type:select|default:new|removeFromForm|required',
        ];
    }

    public function options()
    {
        return [
            'type' => $this->getTypeOptions(),
            'state' => [
                'new' => 'Vytvorený',
                'error' => 'Chybový',
                'ready' => 'Čakajúci',
                'completed' => 'Dokončený',
            ],
        ];
    }

    public function getFilterStates()
    {
        return [
            [
                'name' => _('Čakajúce'),
                'color' => 'orange',
                'active' => function(){
                    return in_array($this->state, ['new', 'ready']);
                },
                'query' => function($query){
                    return $query->whereIn('state', ['new', 'ready']);
                },
            ],
            [
                'name' => _('Úspešny'),
                'color' => '#38c172',
                'active' => function(){
                    return in_array($this->state, ['completed']);
                },
                'query' => function($query){
                    return $query->where('state', 'completed');
                },
            ],
            [
                'name' => _('Chybový'),
                'color' => '#e3342f',
                'active' => function(){
                    return in_array($this->state, ['error']);
                },
                'query' => function($query){
                    return $query->where('state', 'error');
                },
            ]
        ];
    }

    public function scopeAdminRows($query)
    {
        $query->with('logs');
    }

    public function logs()
    {
        return $this->hasMany(ImportsLog::class);
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['last_import'] = ($this->updated_at ?: $this->created_at)->format('d.m.Y H:i');

        return $attributes;
    }

    public function canImport($update = false)
    {
        $importer = $this->getImporter();

        if ( ($importer['autoimport'] ?? true) === false){
            return false;
        }

        if ( $update === true ) {
            return $this->canReimport();
        }

        return true;
    }

    public function canReimport()
    {
        // Allow only in development mode updating existing import
        if ( isDebugMode() === true ){
            return true;
        }

        return false;
    }
}