<?php

namespace AdminHelpers\Importer\Concerns;

use AdminHelpers\Importer\Rules\ImportFileRule;

trait HasImportProcess
{
    private $importerRule;

    /**
     * Bootst import rule
     *
     * @return void
     */
    public function validate()
    {
        // If import rule is already booted
        if ( $this->importerRule ) {
            return;
        }

        $this->importerRule = new ImportFileRule();

        $this->importerRule->bootImport($this);
    }

    /**
     * Run import process
     *
     * @return void
     */
    public function process()
    {
        // Validate before processing. This will not fire if validation is already done.
        $this->validate();

        $this->importerRule->import($this);
    }
}