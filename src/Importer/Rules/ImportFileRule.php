<?php

namespace AdminHelpers\Importer\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Exception;
use Throwable;

class ImportFileRule extends AdminRule
{
    public function creating(AdminModel $row)
    {
        if ( $row->canImport() === false ) {
            return;
        }

        try {
            $row->validate();
        } catch (Exception|Throwable $e){
            autoAjax()->throw($e, 422);
        }
    }

    public function created(AdminModel $row)
    {
        if ( $row->canImport() === false ) {
            return;
        }

        try {
            $row->process();
        } catch (Exception|Throwable $e){
            autoAjax()->throw($e, 422);
        }
    }

    public function updated(AdminModel $row)
    {
        if ( $row->canImport(true) === false ) {
            return;
        }

        try {
            $row->process();
        } catch (Exception|Throwable $e){
            autoAjax()->throw($e, 422);
        }
    }
}