<?php

namespace AdminHelpers\Notifications\Models;

use AdminHelpers\Notifications\Models\NotificationsRecipient;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class AppNotification extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-05-28 14:10:18';

    /*
     * Template name
     */
    protected $name = 'Notifikácie';

    /*
     * Template title
     */
    protected $title = '';

    /*
     * Model Parent
     * Eg. Article::class
     */
    protected $belongsToModel = [];

    protected $publishable = false;

    protected $sortable = false;

    protected $icon = 'fa-bell';

    public $timestamps = false;

    protected $insertable = false;

    // For sending purposes
    public $recipientsKeys = [];
    public $recipientsPivot = [];

    public $orderBy = ['notify_at', 'desc'];

    /*
     * Automatic form and database generator by fields list (prettier-ignore)
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        $apps = config('admin_helpers.notifications.apps');

        return [
            'app' => 'name:Aplikácia|type:select|options:'.implode(',', $apps).'|default:'.($apps[0] ?? '').'|enum|required|inaccessible',
            'code' => 'name:Typ notifikácie|type:select|option::name|max:5|index|required',
            'identifier' => 'name:Typ relácie|max:20|index|inaccessible',
            'data' => 'name:Data|type:json',
            'sent' => 'name:Odoslaná|type:checkbox|default:0|index',
            'notify_at' => 'name:Dátum notifikácie|type:timestamp',
            'created_at' => 'name:Dátum vytvorenia|type:timestamp|default:CURRENT_TIMESTAMP',
        ];
    }

    public function options()
    {
        return [
            'code' => notificationsList()->keyBy('code')->toArray(),
        ];
    }

    public function setRead()
    {
        //Set unread notifications as read for now
        (new NotificationsRecipient)->onlyMine()->whereNull('read_at')->update([
            'read_at' => now(),
        ]);
    }

    public function scopeNewest($query)
    {
        $query->orderBy('created_at', 'DESC')->withoutGlobalScope('order');
    }

    public function scopeOnlyMine($query)
    {
        $query->select($this->getTable().'.*')->withReadState();

        $query->where(function($query){
            //Find by owner
            $query->where(
                $this->getForeignColumn(client()->getTable()),
                client()->getKey()
            );

            $query->orWhereHas('recipients', function($query){
                $query->onlyMine();
            });
        });
    }

    public function scopeWithReadState($query)
    {
        $query
            ->addSelect('notifications_recipients.read_at')
            ->leftJoin('notifications_recipients', function($join){
                $join->on('notifications_recipients.notification_id', '=', $this->getTable().'.id')
                     ->where((new NotificationsRecipient)->getCurrentSelector());
            });
    }

    public function setResponse()
    {
        return $this->setVisible([
            'id', 'type', 'data', 'read_at',
            'title', 'message', 'created_at',
        ])->append([
            'type', 'title', 'message',
        ]);
    }

    public function getColorAttribute()
    {
        if ( $this->termine_id ) {
            return '#F27C49';
        }

        return '#49BFF2';
    }

    public function getIconAttribute()
    {
        return 'notifications_outline';
    }

    public function getByName($name)
    {
        return notificationsList()->keyBy('key')[$name] ?? null;
    }

    public function getByCode($code)
    {
        return notificationsList()->keyBy('code')[$code] ?? null;
    }

    public function getTypeAttribute()
    {
        if ( $code = $this->getByCode($this->code) ){
            return $code['key'];
        }
    }

    public function getTitleAttribute()
    {
        $type = $this->getByCode($this->code);

        return $this->addBindings($this->data['title'] ?? $type['title'] ?? $type['name'] ?? null);
    }

    public function getPushTitleAttribute()
    {
        if ( $title = ($this->data['title'] ?? null) ) {
            return $title;
        }

        $type = $this->getByCode($this->code);

        return $this->addBindings($type['name'] ?? '');
    }

    public function getMessageAttribute()
    {
        $type = $this->getByCode($this->code);

        return $this->addBindings($this->data['message'] ?? $type['message'] ?? null);
    }

    public function getPushMessageAttribute()
    {
        if ( $message = ($this->data['message'] ?? null) ) {
            return $message;
        }

        $type = $this->getByCode($this->code);

        return $this->addBindings($type['message'] ?? '') ?: _('Kliknite pre zobrazenie');
    }

    public function getImageAttribute()
    {
        // Temporary
    }

    public function getPushImageAttribute()
    {
        $type = $this->getByCode($this->code);

        if ( $image = ($type['image'] ?? null) ) {
            return asset('/images/notifications/'.$image);
        }
    }

    public function getIsPersistentAttribute()
    {
        return $this->getByCode($this->code)['persistent'] ?? true;
    }

    public function getPriorityAttribute()
    {
        return $this->getByCode($this->code)['priority'] ?? false;
    }

    private function addBindings($text)
    {
        // Dont cast array
        if ( is_string($text) === false ){
            return $text;
        }

        $data = $this->getNotificationBindings();

        $text = $text ?: '';

        preg_match_all("/\{([^}]+)\}/", $text, $matches);

        foreach ($matches[0] as $i => $wrapper) {
            $match = $matches[1][$i];
            $match = str_replace('#', '', $match); //Trim bolds

            $value = array_get($data, $match) ?: '-';

            $text = str_replace($wrapper, $value, $text);
        }

        return $text;
    }

    private function getNotificationBindings()
    {
        return $this->data ?: [];
    }
}