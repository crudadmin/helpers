<?php

namespace AdminHelpers\Importer\Buttons;

use Admin\Helpers\Button;
use Admin\Eloquent\AdminModel;
use AdminHelpers\Importer\Rules\ImportFileRule;

class ProcessImportButton extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'button';

    //Name of button on hover
    public $name = 'Spracovať import';

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
        $this->active = $row->canProcess();
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $row)
    {
        $rule = new ImportFileRule();

        $rule->bootImport($row);
        $rule->import($row);

        return $this->message(_('Import bol úspešne spracovaný.'));
    }
}