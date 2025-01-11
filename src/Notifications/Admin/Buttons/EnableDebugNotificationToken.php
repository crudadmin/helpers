<?php

namespace AdminHelpers\Notifications\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;

class EnableDebugNotificationToken extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'button';

    //Name of button on hover
    public $name = 'Povoliť zasielať notifikácie v DEV režime';

    //Button classes
    public $class = 'btn-success';

    //Button Icon
    public $icon = 'fa-check';

    /**
     * Here you can set your custom properties for each row
     * @param Admin\Models\Model $row
     */
    public function __construct($row)
    {
        $this->active = !$row->debug;
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $row)
    {
        $row->update([
            'debug' => true,
        ]);

        return $this->toast('Token povolený pre DEV prostredie.');
    }
}