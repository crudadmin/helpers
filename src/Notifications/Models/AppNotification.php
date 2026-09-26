<?php

namespace AdminHelpers\Notifications\Models;

use AdminHelpers\Notifications\Models\NotificationsRecipient;
use Illuminate\Support\Facades\DB;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Illuminate\Support\Arr;

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

    protected $settings = [
        'icon' => 'fa-bell',
    ];

    public $timestamps = false;

    protected $insertable = false;

    protected $inMenu = false;

    protected $withoutParent = true;

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
            Group::fields([
                'app' => 'name:Aplikácia|type:select|options:'.implode(',', $apps).'|default:'.($apps[0] ?? '').'|enum|required|inaccessible',
            ])->if(hasAppsSupport()),
            'code' => 'name:Typ notifikácie|type:select|option::name|max:5|index:created_at|required',
            'identifier' => 'name:Typ relácie|max:20|index|inaccessible',
            'data' => 'name:Data|type:json',
            'sent' => 'name:Odoslaná|type:checkbox|default:0|index',
            'notify_at' => 'name:Dátum notifikácie|type:timestamp',
            'created_at' => 'name:Dátum vytvorenia|type:timestamp|default:CURRENT_TIMESTAMP|index',
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

    /**
     * Notifications visible to the logged user.
     *
     * The three sources are collected with a UNION instead of OR'ed conditions. With an
     * OR, MySQL cannot use any index and walks the whole table backwards evaluating a
     * dependent subquery per row — on 160k notifications that was ~85ms per request,
     * this way each branch is served by its own index and it costs ~2ms.
     *
     * No LIMIT is applied inside the branches on purpose, the scope is used with
     * paginate() as well.
     */
    public function scopeOnlyMine($query)
    {
        $user = auth()->user();

        if ( !($foreignColumn = $this->getForeignColumn($user->getTable())) ){
            throw new \Exception('Foreign column (belongsTo) in notification model could not be found for table: '.$user->getTable());
        }

        $query->select($this->getTable().'.*')->withReadState();

        //Joined as a derived table on purpose. With "id IN (subquery)" MySQL keeps it
        //dependent and re-runs it for every scanned row, a join materializes it once.
        $query->joinSub(
            $this->getMyNotificationIdsQuery($user, $foreignColumn),
            'my_notifications',
            'my_notifications.id',
            '=',
            $this->getTable().'.id'
        );

        $query->onlyAfterNotificationDate();
    }

    /**
     * Ids of every notification the user may see, collected from the three sources.
     *
     * The two branches that have a scope of their own go through it instead of
     * rebuilding the condition here. A project may override those scopes to narrow what
     * its users are allowed to see — Auttia limits non-persistent notifications to the
     * client's own school — and a raw query would hand out every other school's too.
     *
     * The order scope is dropped on the way in: these are subqueries, sorting them costs
     * time and buys nothing, the outer query does the ordering.
     *
     * No LIMIT inside on purpose, the scope is used with paginate() as well.
     */
    private function getMyNotificationIdsQuery($user, $foreignColumn)
    {
        //Find by owner
        return DB::table($this->getTable())
            ->select('id')
            ->where($foreignColumn, $user->getKey())

            //Or I am one of the recipients
            ->union(
                (new NotificationsRecipient)->newQuery()
                    ->withoutGlobalScope('order')
                    ->select('notification_id as id')
                    ->onlyMine()
                    ->toBase()
            )

            //Or it is a global notification created after my registration
            ->union(
                $this->newQuery()
                    ->withoutGlobalScope('order')
                    ->select($this->qualifyColumn('id'))
                    ->isNotPersistent()
                    ->toBase()
            );
    }

    /**
     * Codes of notifications visible to everybody.
     *
     * Casted to strings on purpose — the column is a varchar and the definitions hold
     * integers. Comparing the two makes MySQL convert every row and ignore the index.
     */
    public function getNotPersistentCodes()
    {
        return notificationsList()
            ->where(fn($notif) => ($notif['persistent'] ?? true) === false)
            ->pluck('code')
            ->map(fn($code) => (string) $code)
            ->values()
            ->toArray();
    }

    public function scopeOnlyAfterNotificationDate($query)
    {
        $query->where(function($query){
            $column = $this->getTable().'.notify_at';

            $query->whereNull($column)->orWhere($column, '<=', now());
        });
    }

    public function scopeIsNotPersistent($query)
    {
        $query->whereIn('code', $this->getNotPersistentCodes());

        if ( $user = auth()->user() ){
            $query->where($query->qualifyColumn('created_at'), '>=', $user->created_at);
        }
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
        // A color passed in the notification data wins, projects may override this accessor
        return $this->data['color'] ?? '#49BFF2';
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

    /**
     * Codes of notification types marked instant. Derived from the config, so there is
     * no redundant per-row column — the config is the single source of truth.
     */
    public function getInstantCodes()
    {
        return notificationsList()
            ->filter(fn($type) => ($type['instant'] ?? false) === true)
            ->pluck('code')
            ->all();
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

            $value = Arr::get($data, $match) ?: '-';

            $text = str_replace($wrapper, $value, $text);
        }

        return $text;
    }

    public function getNotificationBindings()
    {
        return $this->data ?: [];
    }
}