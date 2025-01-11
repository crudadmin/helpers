<?php

namespace AdminHelpers\Notifications\Models;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class NotificationsRecipient extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-05-28 15:10:18';

    /*
     * Template name
     */
    protected $name = 'Príjemcovia notifikácie';

    /*
     * Template title
     */
    protected $title = '';

    protected $active = false;

    protected $sortable = false;

    protected $publishable = false;

    public $timestamps = false;

    /*
     * Automatic form and database generator by fields list (prettier-ignore)
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'notification_id' => 'name:Notifikacia|belongsTo:'.notificationModel()->getTable().'|required',
            'table' => 'name:Tabuľka|index:row_id|type:select|options:'.implode(',', config('admin_helpers.notifications.recipients_tables')).'|enum',
            'row_id' => 'name:Záznam|type:integer|max:0',
            'state' => 'name:Send state|type:select|options:sent,unsent,invalid,unknown,blocked,error|enum',
            'read_at' => 'name:Dátum prečitania|type:timestamp',
        ];
    }

    public function scopeOnlyMine($query)
    {
        $query->where($this->getCurrentSelector());
    }

    public function getCurrentSelector()
    {
        return [
            'table' => client()->getTable(),
            'row_id' => client()->getKey(),
        ];
    }
}