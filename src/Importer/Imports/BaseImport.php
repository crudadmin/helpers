<?php

namespace AdminHelpers\Importer\Imports;

use Admin\Eloquent\AdminModel;
use Admin\Core\Helpers\Storage\AdminFile;
use AdminHelpers\Importer\Concerns\HasCastsSupport;
use AdminHelpers\Importer\Utilities\FromXlsToArray;
use AdminHelpers\Importer\Concerns\HasColumnsSupport;

class BaseImport
{
    use HasCastsSupport,
        HasColumnsSupport;

    public AdminModel $import;

    public AdminFile $file;

    public $array;

    public function getColumns()
    {
        return [];
    }

    public function __construct($import)
    {
        $this->import = $import;

        if ( $import->file ) {
            $this->file = $import->file;

            $this->array = (new FromXlsToArray($this->file))->toArray();
        }
    }

    public function getName()
    {
        return $this->import->getSelectOption('type')['name'] ?? 'Import';
    }

    /**
     * Runs validation for given import row
     *
     * @param  mixed $importRow
     * @return void
     */
    public function validate(AdminModel $importRow)
    {
        //..
    }

    /**
     * Runs import for given import row
     *
     * @param  mixed $importRow
     * @return void
     */
    public function import(AdminModel $importRow)
    {
        //..
    }

    /**
     * Prepares rows for import
     *
     * @return void
     */
    public function getRows()
    {
        $rows = $this->array['rows'];

        $rows = $this->castRows($rows);

        return collect($rows);
    }
}
