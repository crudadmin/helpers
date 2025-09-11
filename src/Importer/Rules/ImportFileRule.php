<?php

namespace AdminHelpers\Importer\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Exception;
use Log;
use Throwable;

class ImportFileRule extends AdminRule
{
    public $importer;

    private function setConfigIni()
    {
        //Set limits
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '512M');
    }

    public function bootImport($row)
    {
        $row->runAdminRule('importing');

        /**
         * TODO: ak spadne import, zrusit vsetky transakcie.
         */
        $row->user_id = $row->user_id ?: admin()->getKey();

        if ( !($importer = $row->getImporter()) ){
            $row->update(['state' => 'error']);

            autoAjax()->error(_('Nebol nájdený žiaden importný systém pre tento typ importu.'), 422)->throw();
        }

        $this->setConfigIni();

        try {
            $this->importer = $row->loadImporter();

            $this->importer->checkColumnsAviability();
            $this->importer->checkColumnsFormat();
        } catch (Exception|Throwable $e){
            $row->update(['state' => 'error']);

            Log::error($e);

            autoAjax()->error($e->getMessage(), 422)->throw();
        }
    }

    public function created(AdminModel $row)
    {
        if ( $row->canImport() === false ) {
            return;
        }

        $this->bootImport($row);
        $this->import($row);
    }

    public function updated(AdminModel $row)
    {
        if ( $row->canImport(true) === false ) {
            return;
        }

        $this->bootImport($row);
        $this->import($row);
    }

    public function import($row)
    {
        try {
            $this->importer->import($row);

            $row->update(['state' => 'completed']);
        } catch (Exception|Throwable $e) {
            $row->update(['state' => 'error']);

            $row->logException($e);

            autoAjax()->error($e->getMessage())->throw();
        }
    }
}