<?php

namespace AdminHelpers\Importer\Concerns;

use Admin\Core\Helpers\Store\RequestState;
use Exception;
use Throwable;

trait HasImportProcess
{
    /**
     * Determines if import has been validated.
     *
     * @var bool
     */
    private $validated = false;

    /**
     * * Boots import rule and tries to validate it.
     *
     * @param  mixed  $exception
     * @return void
     */
    public function validate($exception = false)
    {
        if ($this->validated) {
            return $this;
        }

        // Prepare import process
        $this->prepareImport();

        try {
            $this->importer = $this->getImporter();

            $this->importer->checkColumnsAvaiability();
            $this->importer->checkColumnsFormat();

            // Runs user validation for given import row
            $this->importer->validate($this);

            // Set validated flag to true
            $this->validated = true;
        } catch (Exception|Throwable $e) {
            $this->setImportState('error');

            report($e);

            if ($exception) {
                throw $e;
            } else {
                autoAjax()->throw($e, 422);
            }
        }

        return $this;
    }

    /**
     * Run import process.
     *
     * @return void
     */
    public function process()
    {
        // Validate before processing. This will not fire if validation is already done.
        $this->validate();

        try {
            $this->importer->import($this);

            $this->setImportState('completed');
        } catch (Exception|Throwable $e) {
            $this->setImportState('error');

            $this->logException($e);

            throw $e;
        }

        return $this;
    }

    /**
     * Prepares metadata and other settings for import process.
     *
     * @return void
     */
    private function prepareImport()
    {
        // Run importing rule
        $this->runAdminRule('importing');

        // Set config ini for import process
        $this->setImportPHPConfig();

        // Set user id for import
        $this->user_id = $this->user_id ?: admin()?->getKey();

        return $this;
    }

    /**
     * Sets config ini for import process.
     *
     * The limits apply to the rest of the current operation only. Long running processes
     * (Laravel Octane, queue:work) get the previous values back before the next operation,
     * a plain ini_set() would keep them for every following request or job of the worker.
     *
     * @return $this
     */
    public function setImportPHPConfig()
    {
        // Set limits
        RequestState::iniSet('max_execution_time', 1200);
        RequestState::iniSet('memory_limit', '512M');

        return $this;
    }

    /**
     * Sets import state.
     *
     * @param  string  $state
     * @return void
     */
    public function setImportState($state)
    {
        $this->state = $state;

        if ($this->exists) {
            $this->save();
        }

        return $this;
    }
}
